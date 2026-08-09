# Infrastructure, Deployment, and Operations

This chapter describes the infrastructure that is present in the repository today and separates it from the production baseline still to be designed. The current application is one Laravel deployment; its planned modular-monolith organization is established by [ADR 0004](../adr/0004-build-a-laravel-modular-monolith.md). The repository does not yet contain a complete, self-hosted production stack.

## Status at a glance

| Concern            | Current repository evidence                                                                                                       | Operational consequence                                                                                                |
| ------------------ | --------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------- |
| Local runtime      | Docker Compose runs an Nginx container and one development application container                                                  | The application, Vite server, scheduler, and queue worker can run together for development                             |
| Production runtime | A multi-stage Dockerfile builds a PHP-FPM image with compiled frontend assets                                                     | A separate web proxy and runtime stack are required; neither is defined in this repository                             |
| Persistence        | SQLite is the configured default; cache and queue also default to the database                                                    | The database file and local storage directories must survive container replacement                                     |
| Delivery           | Jenkins tests, builds, pushes, and asks Komodo to redeploy the `cook-book` stack on `main` or `master`                            | The Jenkins shared libraries, credentials, Komodo stack definition, and server configuration are external dependencies |
| Recovery           | No backup, restore, retention, or disaster-recovery automation is committed                                                       | Production recovery cannot currently be performed from this repository alone                                           |
| Scaling            | PHP-FPM is stateless only in part; SQLite, local files, file sessions in the Docker environment, and per-container cron are local | The current configuration is suitable for a single application instance, not safe horizontal scaling                   |

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
4. Clears Laravel caches and runs `pnpm install`.
5. Creates the public storage link.
6. Starts cron and Supervisor.

The entire repository is bind-mounted, so dependency installation and generated files can also appear on the host. The stack does not include MySQL, MariaDB, PostgreSQL, Redis, SMTP, or object storage.

## Runtime configuration and persistence

Laravel reads runtime choices from environment variables through the files under [`config/`](../../config). The defaults and tracked examples currently imply the following data layout:

| Data                      | Current driver                                    | Location or table                                                               | Persistence requirement                                                                            |
| ------------------------- | ------------------------------------------------- | ------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| Application data          | SQLite                                            | `DB_DATABASE`; otherwise `database/database.sqlite`                             | Persist and back up the database file                                                              |
| Sessions                  | Database in `.env.example`; file in `.env.docker` | `sessions` table or `storage/framework/sessions`                                | Preserve the chosen backend for uninterrupted login sessions                                       |
| Cache and locks           | Database                                          | `cache` and `cache_locks` tables                                                | Disposable for recovery, but required while the application is running                             |
| Queue and failures        | Database                                          | `jobs`, `job_batches`, and `failed_jobs` tables                                 | Preserve while work is pending or failures require inspection                                      |
| Private uploads           | Local filesystem                                  | `storage/app/private`                                                           | Persist and back up once the application stores user media                                         |
| Public uploads            | Local filesystem                                  | `storage/app/public`, exposed through `public/storage`                          | Persist and back up once the application stores public media                                       |
| Application logs          | `stack` containing `single`                       | `storage/logs/laravel.log`                                                      | Collect externally or preserve long enough to diagnose incidents                                   |
| Scheduler and worker logs | Local files                                       | `storage/logs/scheduler.log` and `storage/logs/queue-worker.log` in development | Collect or rotate explicitly                                                                       |
| Mail                      | Log driver                                        | Application log                                                                 | Password-reset messages are logged rather than delivered until a real mail transport is configured |

The schema for sessions, cache, queues, failed jobs, and authentication data is committed under [`database/migrations/`](../../database/migrations). Supported Laravel connection definitions for MySQL, MariaDB, PostgreSQL, SQL Server, Redis, S3, and several mail transports exist, but configuration support is not evidence that those services are provisioned or tested for this application.

The application exposes Laravel's built-in health endpoint at `/up` through [`bootstrap/app.php`](../../bootstrap/app.php). Neither Dockerfile defines a container health check, and the repository has no evidence that Komodo or an upstream proxy polls this endpoint.

## Production image

[`docker/production/Dockerfile`](../../docker/production/Dockerfile) builds a PHP 8.5 image in stages:

1. The PHP builder copies application sources, installs Composer dependencies without development packages, caches Laravel configuration/routes/views, creates the storage link, and generates Wayfinder output.
2. A Node LTS builder supplies Node and Corepack.
3. The combined build installs JavaScript dependencies and runs `pnpm run build`.
4. The final image contains the application, compiled assets, PHP-FPM configuration, and a cron entry for Laravel's scheduler.

The final container exposes FastCGI on port `9000` and runs PHP-FPM in the foreground. It does not contain Nginx and it does not start a queue worker. A production deployment must therefore provide a FastCGI-aware web proxy and, if queued jobs are used, a separately supervised worker process.

At startup, the [production entrypoint](../../docker/production/start) creates storage directories, applies ownership, optimizes Laravel outside development mode, creates the storage link, runs migrations, starts cron, and then starts PHP-FPM.

### Current production blockers and risks

- The production Dockerfile copies the tracked `.env.docker` into the image. That file currently identifies a local, debug-enabled environment with an empty application key. Runtime environment variables can override those values, but the image is not production-safe without a complete external environment definition.
- `.env.docker` selects `database/db.sqlite`, while SQLite files are excluded from the Docker build context. Unlike the development entrypoint, the production entrypoint does not create the file. A persistent database path must be mounted or an external database must be configured.
- Both entrypoints suppress migration failures and continue starting the application. A deployment can therefore report a running process while its schema is stale.
- The final image stores uploads, framework state, scheduler output, and default logs on its writable container filesystem unless the external stack mounts persistent storage.
- Every application replica starts cron. Multiple replicas would execute the same scheduled work unless scheduling is separated or guarded.
- The image uses local PHP-FPM limits of ten children. No load test or capacity target is committed, so this is a configuration value rather than a demonstrated capacity.
- The development Dockerfile pins pnpm 10.30.2, `package.json` declares pnpm 11.17.0, the production builder activates the latest pnpm, and GitHub Actions uses npm through Composer scripts. These paths do not currently enforce one reproducible JavaScript toolchain.

> **Planned**
>
> Establish a production environment contract before the first public deployment. It should inject a persistent `APP_KEY`, set `APP_ENV=production` and `APP_DEBUG=false`, configure the canonical HTTPS `APP_URL`, secure session cookies, a delivering mail transport, the chosen database and filesystem, and production log routing. Do not derive production secrets from the tracked `.env.docker` file.

## Deployment decision gates

> **Planned**
>
> The committed delivery assets are not yet an executable production topology. Resolve and record these choices before Slice 0 can pass:
>
> - **Database:** either constrain the service to one application replica with a durable SQLite volume, or select and provision an external transactional database. Record backup, restore, migration, and connection-pool behavior for the chosen option.
> - **Media:** choose a durable mounted filesystem or S3-compatible object storage for Recipe, Ingredient, and Store images. Define authorization, retention, deletion, and backup behavior.
> - **Stack ownership:** place the Komodo stack, proxy/TLS contract, networks, health checks, persistent mounts, worker, scheduler, and runtime secrets under version control here or in a named operations repository.
> - **Execution roles:** decide whether web, queue worker, and scheduler use one image with different commands or distinct images. Exactly one scheduler trigger must operate, and workers must restart on deployment.
> - **Recovery:** approve recovery-point and recovery-time objectives, backup retention, restore-test cadence, and rollback ownership.
> - **Observability:** choose log, metric, trace, uptime, and alert destinations and define who responds.
>
> These are hard deployment prerequisites rather than implied properties of Jenkins or the production Dockerfile. Each selected infrastructure option should receive an ADR when it creates meaningful lock-in or a non-obvious trade-off.

## Continuous integration and delivery

Two CI definitions are present.

The [GitHub Actions workflow](../../.github/workflows/tests.yml) runs for pull requests and pushes to `main`. It installs PHP 8.5 and Node 22, runs `composer setup`, and then `composer ci:check`. That path installs and builds frontend dependencies with npm even though pnpm is the declared package manager. It also runs SQLite migrations without creating the ignored `database/database.sqlite` file on a clean checkout, so the workflow is not reproducible until setup creates that file or selects an in-memory database.

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

There is no implemented backup or restore process. No committed job copies the SQLite database or uploaded files, no retention schedule is defined, and no restore test is recorded. Operators must not describe the application as recoverable until both the mutable database and storage contents are covered by a tested process.

Cache data can be rebuilt, but the database may also contain sessions, queued work, and failed-job records in addition to primary application data. Decide deliberately whether those operational tables belong in recovery objectives. The application key is also recovery-critical: changing or losing it invalidates encrypted application data and signed/encrypted cookies.

> **Planned**
>
> Before storing real family data, define recovery-point and recovery-time objectives; automate backups for the production database and uploaded media; keep copies outside the application host; document restoration into an isolated environment; and perform a restore drill. Preserve the application key in the secret-management system, not only in a container or host-local file.

## Scaling model

The current topology should be treated as a single-instance deployment. SQLite and local files are node-local, file sessions cannot roam between instances, each replica would run the scheduler, and the production image has no queue-worker topology.

> **Planned**
>
> Scale only after moving shared mutable state out of individual application containers. A horizontally scalable target would use an external transactional database, shared/object storage for photos, a shared session/cache backend where needed, separately scalable queue workers, exactly one scheduler trigger, and multiple disposable PHP-FPM instances behind a health-checking proxy. Load and recovery tests should determine PHP-FPM, worker, and database capacity rather than copying the current defaults.

## Operational troubleshooting

### The local container exits during startup

Read the complete application-container log:

```bash
docker compose logs laravel
```

Check first for an empty or unwritable `DB_DATABASE`, a missing `APP_KEY`, dependency-installation failures, or storage permissions. Be aware that a message saying `Migration failed` is non-fatal in the current entrypoint; inspect and resolve the underlying migration error before trusting the application.

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
- Production database choice and backup/restore process.
- Production mail delivery, centralized logs, metrics, traces, alerts, and incident response.
- Capacity, availability, recovery, and data-retention objectives.

Treat claims about those concerns as unverified until their authoritative configuration or an operator attestation is added to the documentation evidence set.
