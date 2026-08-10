# Infrastructure, Deployment, and Operations

This chapter describes the infrastructure that is present in the repository and the user-attested production profile operated outside it. The current application is one Laravel deployment; its planned modular-monolith organization is established by [ADR 0004](../adr/0004-build-a-laravel-modular-monolith.md). The Komodo stack is configured directly on the production server and is intentionally not stored in a repository, so repository evidence cannot verify its live settings.

## Status at a glance

| Concern            | Current repository evidence                                                                                                       | Operational consequence                                                                                                |
| ------------------ | --------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------- |
| Local runtime      | Docker Compose runs an Nginx container and one development application container                                                  | The application, Vite server, scheduler, and queue worker can run together for development                             |
| Production runtime | A multi-stage Dockerfile builds a PHP-FPM image; the user attests that Komodo runs one application container on the server       | The proxy and stack are externally managed and cannot be reproduced from this repository                               |
| Local persistence  | SQLite is the development and test default; cache and queue also default to the database                                           | Local setup needs a writable SQLite file                                                                               |
| Production database | MariaDB is selected in ADR 0005 and runs on the same host as the application according to the user attestation                   | Hostname, version, credentials, exposure, and live availability remain server configuration                            |
| Delivery           | Jenkins tests, builds, pushes, and asks Komodo to redeploy the `cook-book` stack on `main` or `master`                            | The Jenkins shared libraries, credentials, Komodo stack definition, and server configuration are external dependencies |
| Recovery           | No automated backup, restore, retention, or disaster-recovery objective is required for this personal project                    | Correctly configured persistent database/media storage should survive container recreation but may be lost with the host |
| Scaling            | The selected production profile is one application container, one host-local MariaDB service, and one persistent filesystem mount | Horizontal scaling is outside the current profile; the live mount remains unverified                                    |

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
| Application data          | SQLite locally; MariaDB in production             | Local `DB_DATABASE` file or the configured MariaDB schema                       | Persist MariaDB outside the application container; backup is optional under the accepted risk profile |
| Sessions                  | Database in the local and production examples     | `sessions` table                                                                | Preserve the chosen database for uninterrupted login sessions                                       |
| Cache and locks           | Database                                          | `cache` and `cache_locks` tables                                                | Disposable for recovery, but required while the application is running                             |
| Queue and failures        | Database locally; synchronous in production       | Local `jobs`, `job_batches`, and `failed_jobs`; no production queue persistence | Add a supervised worker before changing production back to an asynchronous connection               |
| Private uploads           | Local filesystem                                  | `storage/app/private` beneath the `/var/www/storage/app` persistent mount       | Keep Family media private and preserve the mount across container recreation                        |
| Public uploads            | Local filesystem                                  | `storage/app/public` beneath the same mount, exposed through `public/storage`   | Use only for deliberately public assets; Family media remains private by default                     |
| Application logs          | Local `stack`/`single`; production example `stderr` | Local `storage/logs/laravel.log` or the production container's standard error | External collection and retention are optional for the personal profile                              |
| Scheduler and worker logs | Local files                                       | `storage/logs/scheduler.log` in both images and `queue-worker.log` in development | Production scheduler output is ephemeral unless separately mounted or collected                     |
| Mail                      | Local log driver; production example SMTP         | Local application log or the configured production SMTP service                 | Inject and verify production SMTP credentials before password resets can be delivered               |

The schema for sessions, cache, queues, failed jobs, and authentication data is committed under [`database/migrations/`](../../database/migrations). Supported Laravel connection definitions for MySQL, MariaDB, PostgreSQL, SQL Server, Redis, S3, and several mail transports exist, but configuration support is not evidence that those services are provisioned or tested for this application.

The application exposes Laravel's built-in health endpoint at `/up` through [`bootstrap/app.php`](../../bootstrap/app.php). For the single-container profile, use it as the minimal Komodo or proxy health signal: it returns success when Laravel boots and intentionally does not turn a transient MariaDB outage into an application restart loop. Neither Dockerfile defines a container health check, and the repository cannot verify whether the external stack currently polls the route.

## Production image

[`docker/production/Dockerfile`](../../docker/production/Dockerfile) builds a PHP 8.5 image in stages:

1. The PHP builder copies application sources, installs Composer dependencies without development packages, and generates Wayfinder output without copying an environment file or caching runtime configuration.
2. A Node LTS builder supplies Node and Corepack.
3. The combined build installs JavaScript dependencies and runs `pnpm run build`.
4. The final image contains the application, compiled assets, PHP-FPM configuration, and a cron entry for Laravel's scheduler.

The final container exposes FastCGI on port `9000` and runs PHP-FPM in the foreground. It does not contain Nginx and it does not start a queue worker. A production deployment must therefore provide a FastCGI-aware web proxy and, if queued jobs are used, a separately supervised worker process.

At startup, the [production entrypoint](../../docker/production/start) creates storage directories, applies ownership, caches configuration and other Laravel optimizations from runtime-injected values, creates the storage link, and runs migrations. A migration failure terminates startup before cron or PHP-FPM begins. A successful startup then starts cron and PHP-FPM.

### Current production constraints and risks

- MariaDB runs on the same host according to the user attestation, but its supported server version, credentials, network exposure, and connection limits are not repository evidence. Keep it off the public network unless a reviewed TLS configuration protects the connection.
- The image deliberately contains no `.env` or build-time Laravel configuration cache. The external deployment must inject every required runtime value; `.env.production.example` is a non-secret contract, not deployable credentials.
- Komodo must persist `/var/www/storage/app`; without that mount, uploaded media is lost when the container is replaced. The repository cannot verify the live mount.
- The final image contains no production queue worker. The production environment therefore uses the synchronous queue connection while the application has no queued jobs.
- The single application container runs migrations during startup before PHP-FPM. Adding replicas requires a release job or another migration-serialization mechanism before concurrent startup.
- The container starts cron, but no application-specific scheduled commands are registered. Adding replicas or scheduled work requires exactly one scheduler trigger.
- `/up` is deliberately shallow. It does not prove MariaDB, mail, writable media storage, or the external proxy is healthy.
- The image uses local PHP-FPM limits of ten children. No load test or capacity target is committed, so this is a configuration value rather than a demonstrated capacity.

The development image, production image, package manifest, and clean-checkout setup use pnpm 11.17.0 with the committed frozen lockfile. The native clean-checkout and production-image build have both been exercised successfully; this does not prove the external deployment topology.

The tracked `.env.production.example` represents the accepted non-secret runtime contract: MariaDB, synchronous jobs, stderr logging, SMTP, secure database sessions, and the private local filesystem. Komodo must inject a persistent `APP_KEY`, reviewed MariaDB and mail credentials, the canonical HTTPS `APP_URL`, and the actual server hostnames without committing those values.

## Selected production profile

The user supplied the following personal-project choices on 2026-08-10, recorded in [ADR 0006](../adr/0006-use-a-single-host-personal-production-profile.md):

- **Stack ownership:** Komodo configuration lives only on the production server.
- **Database:** MariaDB runs on the same host; SQLite remains the development and test database.
- **Media:** Family media uses the private local disk persisted from `/var/www/storage/app`. S3 is a future migration option, not a current requirement.
- **Execution:** one application image and one container run startup migrations and PHP-FPM. The existing image also starts cron.
- **Recovery:** no automated backup, restore, retention, RPO, or RTO is required for the personal project.
- **Observability:** stderr logs are sufficient for now. The existing OpenTelemetry stack may be connected later.

The repository derives two operational defaults from those choices: production uses synchronous jobs while there is no worker, and `/up` is the recommended shallow Komodo or proxy health signal. The exact `/var/www/storage/app` mount is the required Laravel path for the selected private local disk. These are repository recommendations and configuration consequences, not claims that the user supplied those exact values or that Komodo already applies them.

These choices resolve the design gates but do not verify the external server. Before declaring the deployment acceptance check complete, observe that the Komodo container boots, migrations succeed, `/up` returns HTTP 200 through the proxy, and both a MariaDB record and a private test file survive container recreation.

### External acceptance checklist

Run this checklist from the Komodo application console and an external client without exposing credentials or using real Family data:

1. Record the deployed Git commit or immutable image digest, timestamp, and the MariaDB server version shown by `php artisan db:show --database=mariadb --no-interaction`.
2. Record the application-container startup log through the successful migration line and PHP-FPM start. A swallowed or missing migration result fails the check.
3. From outside the server, run `curl -fsS -o /dev/null -w '%{http_code}\n' https://cookbook.example.com/up` using the real canonical host and record the HTTP 200 result.
4. Create synthetic sentinels through Laravel's configured services:

   ```bash
   php artisan tinker --no-interaction --execute 'cache()->forever("slice0:acceptance", "before-recreate");'
   php artisan tinker --no-interaction --execute 'Illuminate\Support\Facades\Storage::disk("local")->put("slice0-acceptance.txt", "before-recreate");'
   ```

5. Recreate only the application container in Komodo. Do not recreate MariaDB or delete/recreate the persistent storage mount.
6. Confirm both sentinels survived:

   ```bash
   php artisan tinker --no-interaction --execute 'dump(cache()->get("slice0:acceptance"));'
   php artisan tinker --no-interaction --execute 'dump(Illuminate\Support\Facades\Storage::disk("local")->get("slice0-acceptance.txt"));'
   ```

   Both commands must output `before-recreate`. This proves the configured database-backed cache and private filesystem survived application-container replacement; it does not prove recovery from host loss.
7. Remove the synthetic data:

   ```bash
   php artisan tinker --no-interaction --execute 'cache()->forget("slice0:acceptance");'
   php artisan tinker --no-interaction --execute 'Illuminate\Support\Facades\Storage::disk("local")->delete("slice0-acceptance.txt");'
   ```

Record only non-secret results. Keep Komodo configuration, environment values, database credentials, and mail credentials on the server.

## Continuous integration and delivery

The [Jenkins pipeline](../../Jenkinsfile) is the repository's sole authoritative CI and delivery definition. No alternate CI pipeline definition is retained in the repository while Jenkins owns CI.

Current automated application tests run only on SQLite. The delivery contract
checks that MariaDB is selected and that the production image includes its PDO
driver, but it does not connect to the host MariaDB server. Run migrations and
database-sensitive constraint/query tests against the selected server version
before product migrations ship; this may be an explicit deployment check rather
than a repository-hosted CI service for the personal profile.

Jenkins depends on organization-provided `dockerHelpers`, `testing`, and `deploy` shared libraries. Its relevant flow is:

1. Authenticate to Scaleway's test registry using the `scaleway_secret_key` Jenkins credential.
2. Install dependencies and generate Wayfinder routes in test images.
3. Run PHP tests, PHPStan, Pint, Vitest, and TypeScript checks in parallel.
4. On change requests, build the production Dockerfile as an additional check.
5. On `main` or `master`, build a multi-platform image named `cook-book-shopping-list`, push `latest` plus any Git tags that point at the commit to the Scaleway application registry, and invoke the external Komodo stack named `cook-book`.

The `Jenkinsfile` does not define repository-visible job discovery, webhook or polling triggers, reported commit-status names, or branch-protection rules. The external Jenkins and source-control configuration must ensure that change requests and pushes to `main` or `master` start this pipeline, report its result, and require the successful Jenkins status before protected branches can merge. Verify those settings on the server; without them, the repository contains no fallback CI gate.

The visible pipeline explicitly requests PHP tests, PHPStan, Pint, Vitest, and TypeScript checks. It does not explicitly invoke ESLint or Prettier, and the external `testing` shared-library implementation is not repository evidence that it adds those checks. Until the external pipeline proves that coverage or the commands become repository-visible Jenkins steps, run the mandatory frontend gates documented in [Local development](local-development.md#testing-and-quality-gates) and [AGENTS.md](../../AGENTS.md) before merging frontend changes.

The repository does not contain the shared-library implementations, Jenkins job and status-gate configuration, registry retention policy, Komodo endpoint, stack manifest, reverse-proxy configuration, TLS configuration, runtime environment, volume mounts, or rollback policy. Consequently, Jenkins is evidence of an automated handoff, not a reproducible standalone deployment runbook.

The pipeline calls the external `deployKomodoStack` helper but contains no post-deploy request to `/up`, no assertion that startup migrations succeeded, and no database or filesystem persistence check. A successful Jenkins stage therefore does not satisfy Slice 0's external completion gate.

> **Planned**
>
> If deployment risk grows beyond the personal-project profile, make releases immutable and reversible: deploy a commit- or digest-specific image rather than relying only on `latest`, retain a known-good image, and add a health-checked rollback procedure. Until then, keep a private server-side checklist of the Komodo stack's required services, mounts, networks, secrets, and proxy contract because the user has explicitly chosen not to store that configuration in a repository.

## Recovery and data protection

There is no implemented backup or restore process, and none is required for the selected personal-project profile. When correctly configured, persistent MariaDB storage and the `/var/www/storage/app` mount should protect data across application-container replacement; that behavior has not been observed from this repository and does not protect against host loss, corruption, or operator error. Do not describe the application as recoverable.

Cache data can be rebuilt, but the database may also contain sessions, queued work, and failed-job records in addition to primary application data. Decide deliberately whether those operational tables belong in recovery objectives. The application key is also recovery-critical: changing or losing it invalidates encrypted application data and signed/encrypted cookies.

Preserve the application key in Komodo's runtime configuration rather than only inside a replaceable container. Revisit backups, retention, and restore testing if the stored data becomes valuable enough that host loss is unacceptable.

## Scaling model

The selected production topology is intentionally one application container on one host, with host-local MariaDB and a required persistent local media mount. Its live persistence remains unverified. It is not horizontally scalable and does not claim to be highly available.

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

The local stack starts one database queue worker. Inspect `storage/logs/queue-worker.log` and the `failed_jobs` table. Production uses the synchronous queue connection while no worker or queued jobs are required. If production changes to `database` or another asynchronous connection, add and supervise a worker before dispatching work.

### Scheduled work runs more than once

Each application container starts cron. Verify the replica count and ensure only one scheduler is active. No application-specific scheduled commands are currently registered in [`routes/console.php`](../../routes/console.php), but this becomes important as scheduled work is added.

### Health checks disagree with actual readiness

`/up` proves that Laravel can answer the framework health route; it does not verify MariaDB, mail delivery, or writable persistent storage. That shallow behavior is intentional for the single-container personal profile so dependency outages do not trigger restart loops. Configure Komodo or the proxy to poll it, and diagnose dependencies separately when application requests still fail.

## Evidence and known gaps

This chapter is based on repository configuration, container definitions, migrations, package scripts, and CI files. It deliberately does not use the connected Laravel Boost database, in accordance with [documentation decision DOC-0001](../documentation-decisions.md#doc-0001--exclude-the-connected-laravel-boost-database-from-documentation-evidence).

The following cannot be verified from this repository:

- The actual public host, reverse proxy, TLS termination, DNS, firewall, and runtime topology.
- The Komodo stack definition, deployed environment variables, persistent mounts, and active `/up` health check.
- Scaleway registry access and image-retention behavior.
- Provisioned MariaDB version, credentials, network exposure, availability, and persistent data location.
- Production mail delivery and any optional OpenTelemetry integration.
- Live persistence across container recreation and actual capacity.

Treat claims about those concerns as unverified until their authoritative configuration or an operator attestation is added to the documentation evidence set.
