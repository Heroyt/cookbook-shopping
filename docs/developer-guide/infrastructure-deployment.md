# Infrastructure, Deployment, and Operations

This chapter describes the infrastructure that is present in the repository today and separates it from the production baseline still to be designed. The current application is one Laravel deployment; its planned modular-monolith organization is established by [ADR 0004](../adr/0004-build-a-laravel-modular-monolith.md). The repository does not yet contain a complete, self-hosted production stack.

## Status at a glance

| Concern            | Current repository evidence                                                                                                       | Operational consequence                                                                                                |
| ------------------ | --------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------- |
| Local runtime      | Docker Compose runs an Nginx container and one development application container                                                  | The application, Vite server, scheduler, and queue worker can run together for development                             |
| Production runtime | A multi-stage Dockerfile builds a PHP-FPM image with compiled frontend assets                                                     | A separate web proxy and runtime stack are required; neither is defined in this repository                             |
| Local persistence  | SQLite is the development and test default; cache and queue also default to the database                                           | Local setup needs a writable SQLite file                                                                               |
| Production database | MariaDB is selected in ADR 0005 and `.env.production.example`; no database service is committed                                  | Provisioning, versioning, credentials, backup, restore, and availability remain deployment responsibilities            |
| Delivery           | Jenkins tests, builds, pushes, and asks Komodo to redeploy the `cook-book` stack on `main` or `master`                            | The Jenkins shared libraries, credentials, Komodo stack definition, and server configuration are external dependencies |
| Recovery           | No backup, restore, retention, or disaster-recovery automation is committed                                                       | Production recovery cannot currently be performed from this repository alone                                           |
| Scaling            | MariaDB is external to application roles, but local files and per-container cron remain local                                      | Horizontal scaling remains unsafe until storage and execution-role gates are resolved                                  |

## Local Docker topology

The local stack in [`docker-compose.yml`](../../docker-compose.yml) has two services on one bridge network:

- `nginx` publishes `${APP_PORT:-80}` and serves files from `public/`. PHP requests are forwarded to `laravel:9000`; Vite development paths are proxied to `laravel:5173`.
- `laravel` is built from [`docker/dev/Dockerfile`](../../docker/dev/Dockerfile), publishes `${VITE_PORT:-5173}`, and bind-mounts the repository at `/var/www`.

The application container uses Supervisor to run PHP-FPM, one database-backed queue worker, and Vite. Its entrypoint also starts cron, which invokes `php artisan schedule:run` every minute. The queue worker uses three attempts, sleeps for three seconds when idle, and restarts after at most one hour. See the [development entrypoint](../../docker/dev/start), [Supervisor configuration](../../docker/dev/supervisord.conf), and [queue-worker configuration](../../docker/dev/supervisor-queue-worker.conf).

### Starting and inspecting the local stack

Prepare `.env` before starting the stack. At minimum, use a non-empty `APP_KEY` and give SQLite an explicit path that is writable inside the container, such as:

```dotenv
APP_URL=http://localhost
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/database/database.sqlite
```

The tracked [environment example](../../.env.example) leaves `DB_DATABASE` commented out, but the development entrypoint calls `touch "${DB_DATABASE}"`; an empty value therefore prevents startup. Keep `APP_URL` consistent with `APP_PORT` when using a non-default host port.

Start the services and follow their logs:

```bash
docker compose up --build
```

In another terminal, inspect service state or application logs:

```bash
docker compose ps
docker compose logs laravel nginx
```

The entrypoint performs the following work every time the development container starts:

1. Creates writable framework and storage directories.
2. Runs `composer install` when `APP_ENV=local` or `APP_DEBUG=true`.
3. Ensures the configured SQLite file exists and runs migrations.
4. Clears Laravel caches and runs `pnpm install --frozen-lockfile`.
5. Creates the public storage link.
6. Starts cron and Supervisor.

The entire repository is bind-mounted, so dependency installation and generated files can also appear on the host. The stack does not include MySQL, MariaDB, PostgreSQL, Redis, SMTP, or object storage.

## Runtime configuration and persistence

Laravel reads runtime choices from environment variables through the files under [`config/`](../../config). The defaults and tracked examples currently imply the following data layout:

| Data                      | Current driver                                    | Location or table                                                               | Persistence requirement                                                                            |
| ------------------------- | ------------------------------------------------- | ------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| Application data          | SQLite locally; MariaDB in production             | Local `DB_DATABASE` file or the configured MariaDB schema                       | Back up the production MariaDB service and preserve local databases when needed                     |
| Sessions                  | Database in the local and production examples     | `sessions` table                                                                | Preserve the chosen database for uninterrupted login sessions                                       |
| Cache and locks           | Database                                          | `cache` and `cache_locks` tables                                                | Disposable for recovery, but required while the application is running                             |
| Queue and failures        | Database                                          | `jobs`, `job_batches`, and `failed_jobs` tables                                 | Preserve while work is pending or failures require inspection                                      |
| Private uploads           | Local filesystem                                  | `storage/app/private`                                                           | Persist and back up once the application stores user media                                         |
| Public uploads            | Local filesystem                                  | `storage/app/public`, exposed through `public/storage`                          | Persist and back up once the application stores public media                                       |
| Application logs          | Local `stack`/`single`; production example `stderr` | Local `storage/logs/laravel.log` or the production container's standard error | Collect externally and retain long enough to diagnose incidents                                     |
| Scheduler and worker logs | Local files                                       | `storage/logs/scheduler.log` and `storage/logs/queue-worker.log` in development | Collect or rotate explicitly                                                                       |
| Mail                      | Local log driver; production example SMTP         | Local application log or the configured production SMTP service                 | Inject and verify production SMTP credentials before password resets can be delivered               |

The schema for sessions, cache, queues, failed jobs, and authentication data is committed under [`database/migrations/`](../../database/migrations). Supported Laravel connection definitions for MySQL, MariaDB, PostgreSQL, SQL Server, Redis, S3, and several mail transports exist, but configuration support is not evidence that those services are provisioned or tested for this application.

The application exposes Laravel's built-in health endpoint at `/up` through [`bootstrap/app.php`](../../bootstrap/app.php). Neither Dockerfile defines a container health check, and the repository has no evidence that Komodo or an upstream proxy polls this endpoint.

## Production image

[`docker/production/Dockerfile`](../../docker/production/Dockerfile) builds a PHP 8.5 image in stages:

1. The PHP builder copies application sources, installs Composer dependencies without development packages, and generates Wayfinder output without copying an environment file or caching runtime configuration.
2. A Node LTS builder supplies Node and Corepack.
3. The combined build installs JavaScript dependencies and runs `pnpm run build`.
4. The final image contains the application, compiled assets, PHP-FPM configuration, and a cron entry for Laravel's scheduler.

The final container exposes FastCGI on port `9000` and runs PHP-FPM in the foreground. It does not contain Nginx and it does not start a queue worker. A production deployment must therefore provide a FastCGI-aware web proxy and, if queued jobs are used, a separately supervised worker process.

At startup, the [production entrypoint](../../docker/production/start) creates storage directories, applies ownership, caches configuration and other Laravel optimizations from runtime-injected values, creates the storage link, and runs migrations. A migration failure terminates startup before cron or PHP-FPM begins. A successful startup then starts cron and PHP-FPM.

### Current production blockers and risks

- The selected MariaDB production database is not provisioned by any committed stack, and its supported server version, credentials, availability, connection limits, backup, and restore behavior are not defined here.
- The image deliberately contains no `.env` or build-time Laravel configuration cache. The external deployment must inject every required runtime value; `.env.production.example` is a non-secret contract, not deployable credentials.
- The final image stores uploads, framework state, scheduler output, and default logs on its writable container filesystem unless the external stack mounts persistent storage.
- The final image contains no readiness check or production queue-worker process.
- Every application container runs migrations during startup. Until the
  production rollout serializes migrations through a release job or one
  designated instance, multiple replicas must not start concurrently against
  an unapplied schema. The rollout and rollback contract must keep application
  and schema versions compatible.
- Every application replica starts cron. Multiple replicas would execute the same scheduled work unless scheduling is separated or guarded.
- The image uses local PHP-FPM limits of ten children. No load test or capacity target is committed, so this is a configuration value rather than a demonstrated capacity.

The development image, production image, package manifest, clean-checkout setup, and GitHub Actions path now use pnpm 11.17.0 with the committed frozen lockfile. The native clean-checkout and production-image build have both been exercised successfully; this does not prove the external deployment topology.

> **Planned**
>
> Complete the production environment contract before the first public deployment. The tracked `.env.production.example` selects MariaDB and names non-secret settings, while the deployment must inject a persistent `APP_KEY`, reviewed MariaDB credentials, the canonical HTTPS `APP_URL`, a delivering mail transport, the selected durable filesystem, and production log routing.

## Deployment decision gates

> **Planned**
>
> The committed delivery assets are not yet an executable production topology. Resolve and record these choices before Slice 0 can pass:
>
> - **Database:** MariaDB is selected for production and SQLite remains the development/test database. Provision the MariaDB service and record its supported version, availability, credentials, backup, restore, migration, and connection-pool behavior. Decide whether the connection is restricted to a private trusted network or protected with TLS; when TLS is required, inject and verify the CA and connection options. Verify database-specific migrations and constraints against MariaDB before product tables ship.
> - **Media:** choose a durable mounted filesystem or S3-compatible object storage for Recipe, Ingredient, and Store images. Define authorization, retention, deletion, and backup behavior.
> - **Stack ownership:** place the Komodo stack, proxy/TLS contract, networks, health checks, persistent mounts, worker, scheduler, and runtime secrets under version control here or in a named operations repository.
> - **Execution roles:** decide whether web, queue worker, and scheduler use one image with different commands or distinct images. Exactly one scheduler trigger must operate, and workers must restart on deployment.
> - **Recovery:** approve recovery-point and recovery-time objectives, backup retention, restore-test cadence, and rollback ownership.
> - **Observability:** choose log, metric, trace, uptime, and alert destinations and define who responds.
>
> These are hard deployment prerequisites rather than implied properties of Jenkins or the production Dockerfile. Each selected infrastructure option should receive an ADR when it creates meaningful lock-in or a non-obvious trade-off.

## Continuous integration and delivery

Two CI definitions are present.

The [GitHub Actions workflow](../../.github/workflows/tests.yml) runs for pull requests and pushes to `main`. It installs PHP 8.5 and Node 22, activates pnpm 11.17.0, runs `composer setup`, and then `composer ci:check`. The setup script creates the ignored SQLite file before migration, installs the frozen pnpm dependency graph, and builds production assets. An isolated clean-checkout execution of that sequence succeeds.

Current automated application tests run only on SQLite. The delivery contract
checks that MariaDB is selected and that the production image includes its PDO
driver, but it does not connect to a MariaDB server. Once the server version and
provisioning are selected, CI must add a MariaDB migration, constraint, and
query-compatibility job before database-sensitive product migrations ship.

The [Jenkins pipeline](../../Jenkinsfile) is the deployment path. It depends on organization-provided `dockerHelpers`, `testing`, and `deploy` shared libraries. Its relevant flow is:

1. Authenticate to Scaleway's test registry using the `scaleway_secret_key` Jenkins credential.
2. Install dependencies and generate Wayfinder routes in test images.
3. Run PHP tests, PHPStan, Pint, Vitest, and TypeScript checks in parallel.
4. On change requests, build the production Dockerfile as an additional check.
5. On `main` or `master`, build a multi-platform image named `cook-book-shopping-list`, push `latest` plus any Git tags that point at the commit to the Scaleway application registry, and invoke the external Komodo stack named `cook-book`.

The repository does not contain the shared-library implementations, registry retention policy, Komodo endpoint, stack manifest, reverse-proxy configuration, TLS configuration, runtime environment, volume mounts, or rollback policy. Consequently, Jenkins is evidence of an automated handoff, not a reproducible standalone deployment runbook.

> **Planned**
>
> Make releases immutable and reversible: deploy a commit- or digest-specific image rather than relying only on `latest`, retain at least one known-good image, define a health-checked rollout and rollback procedure, and fail deployment when migrations fail. Record the external Komodo stack's required services, mounts, networks, secrets, and proxy contract in version-controlled infrastructure or an explicitly governed operations repository.

## Recovery and data protection

There is no implemented backup or restore process. No committed job backs up the selected MariaDB production database or uploaded files, no retention schedule is defined, and no restore test is recorded. Operators must not describe the application as recoverable until both the mutable database and storage contents are covered by a tested process.

Cache data can be rebuilt, but the database may also contain sessions, queued work, and failed-job records in addition to primary application data. Decide deliberately whether those operational tables belong in recovery objectives. The application key is also recovery-critical: changing or losing it invalidates encrypted application data and signed/encrypted cookies.

> **Planned**
>
> Before storing real family data, define recovery-point and recovery-time objectives; automate backups for the production database and uploaded media; keep copies outside the application host; document restoration into an isolated environment; and perform a restore drill. Preserve the application key in the secret-management system, not only in a container or host-local file.

## Scaling model

No committed production topology exists yet. MariaDB removes the selected production database from individual application containers, but local files remain node-local, each web replica would run the scheduler, and the production image has no queue-worker topology.

> **Planned**
>
> Scale only after moving shared mutable state out of individual application containers. A horizontally scalable target would use an external transactional database, shared/object storage for photos, a shared session/cache backend where needed, separately scalable queue workers, exactly one scheduler trigger, and multiple disposable PHP-FPM instances behind a health-checking proxy. Load and recovery tests should determine PHP-FPM, worker, and database capacity rather than copying the current defaults.

## Operational troubleshooting

### The local container exits during startup

Read the complete application-container log:

```bash
docker compose logs laravel
```

Check first for a missing `APP_KEY`, invalid database settings, an unavailable MariaDB service in production, an empty or unwritable SQLite path in development, dependency-installation failures, or storage permissions. Migration and dependency failures now terminate the entrypoint; resolve the reported error before restarting it.

### The page loads without frontend updates

Confirm that the Vite process is running under Supervisor and that Nginx can reach `laravel:5173`. The relevant output is included in:

```bash
docker compose logs laravel nginx
```

The development Nginx proxy handles `/resources`, `/node_modules`, `@vite`, `@fs`, and `@id` paths. The production image does not run Vite and instead requires the compiled `public/build` assets generated during its build.

### Queued work does not run

The local stack starts one database queue worker. Inspect `storage/logs/queue-worker.log` and the `failed_jobs` table. The production image starts no worker, so verify that the external deployment provides one before expecting asynchronous jobs to run.

### Scheduled work runs more than once

Each application container starts cron. Verify the replica count and ensure only one scheduler is active. No application-specific scheduled commands are currently registered in [`routes/console.php`](../../routes/console.php), but this becomes important as scheduled work is added.

### Health checks disagree with actual readiness

`/up` proves that Laravel can answer the framework health route; it does not currently verify database migrations, queue-worker health, mail delivery, writable persistent storage, or backup freshness. Use it as a liveness signal only until explicit readiness checks are implemented.

## Evidence and known gaps

This chapter is based on repository configuration, container definitions, migrations, package scripts, and CI files. It deliberately does not use the connected Laravel Boost database, in accordance with [documentation decision DOC-0001](../documentation-decisions.md#doc-0001--exclude-the-connected-laravel-boost-database-from-documentation-evidence).

The following cannot be verified from this repository:

- The actual public host, reverse proxy, TLS termination, DNS, firewall, and runtime topology.
- The Komodo stack definition, deployed environment variables, persistent mounts, health checks, and rollback behavior.
- Scaleway registry access and image-retention behavior.
- Provisioned MariaDB version, topology, credentials, availability, and backup/restore process.
- Production mail delivery, centralized logs, metrics, traces, alerts, and incident response.
- Capacity, availability, recovery, and data-retention objectives.

Treat claims about those concerns as unverified until their authoritative configuration or an operator attestation is added to the documentation evidence set.
