# Local Development

This chapter covers the current authenticated Laravel/Inertia shell and Family
Access workflow plus the Cookbook Store and Store Section tracers. Local setup
and migrations create the Family, Store, and Store Section tables and Current
Family preference; focused tests exercise provisioning, Family lifecycle, Store
create/rename normalization and duplicate handling, Store deletion, Store
Section creation/listing and colour validation, equal rights, cross-Family isolation, and account-deletion
protection. The rest of Cookbook,
meal planning, and Shopping List generation do not exist yet. See
[Current Application](current-application.md) for the implemented boundary.

## Choose a runtime

Native development is the most direct route when PHP, Composer, Node.js, and
pnpm are already installed. Docker Compose is available when a PHP 8.5
container is preferable.

For native development, install:

- PHP 8.5 to match development, production, and CI images. The Composer
  constraint permits PHP 8.3 or newer, but 8.5 is the project runtime target.
- Composer.
- Node.js compatible with the current Vite toolchain.
- pnpm 11.17.0, as declared by the package manifest.
- SQLite and the PHP SQLite extension for the default local database.

The version sources are [composer.json](../../composer.json),
[package.json](../../package.json), and [pnpm-lock.yaml](../../pnpm-lock.yaml).

## Native setup

From the repository root:

```bash
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate
pnpm install --frozen-lockfile
pnpm build
```

The default `.env.example` uses SQLite plus database-backed sessions, cache,
and queues. Migrations therefore create all state needed for a basic local run.
Do not commit `.env` or a populated SQLite database.

The repository also provides `composer setup`, which performs this clean-checkout
sequence: it installs Composer dependencies, creates `.env` and the SQLite file
when missing, generates the application key, migrates, installs the frozen pnpm
dependency graph, and builds production assets.

Treat `composer setup` as a clean-checkout bootstrap command. It generates a new
`APP_KEY` even when `.env` already exists, so rerunning it can invalidate
encrypted values, cookies, and sessions. For an established checkout, run only
the required `composer install`, `php artisan migrate`, frozen pnpm install, or
frontend build command instead.

Optional development data can be added with:

```bash
php artisan db:seed
```

The current seeder creates one test User. Inspect
[DatabaseSeeder.php](../../database/seeders/DatabaseSeeder.php) before using it
in any shared environment.

For an ordinary local or deployed account, use the operator-only interactive
command instead of the development seeder:

```bash
php artisan user:create user@example.com "Example User"
```

The command prompts for a password and confirmation without echoing them.
Public self-registration remains disabled, and adding a Family member by email
does not create a User.

## Start the native application

Start all registered development processes with:

```bash
composer dev
```

That command delegates to `php artisan dev`, which currently starts:

- the Laravel development server at `http://localhost:8000`;
- a queue listener;
- Laravel Pail for logs; and
- the Vite development server through `pnpm run dev`.

Keep `APP_URL=http://localhost:8000` when using that default server. Passkeys
derive their relying-party and allowed-origin configuration from `APP_URL`, so
the browser origin and configuration must match. Use HTTPS for non-local
WebAuthn environments.

To inspect the live process definition after framework or dependency updates,
run `php artisan dev:list`. To run parts independently, use
`php artisan serve`, `php artisan queue:work`, `php artisan pail`, and
`pnpm dev` in separate terminals.

## Docker Compose setup

The Compose stack contains only the Laravel and Nginx services. It bind-mounts
the working tree, installs dependencies in local/debug mode, runs migrations,
starts PHP-FPM, Vite, a database queue worker, and the scheduler, and exposes
Nginx on `APP_PORT` (port 80 by default).

Prepare an environment file before the first start:

```bash
cp .env.example .env
```

For SQLite in the container, add an explicit container path to `.env`:

```dotenv
APP_URL=http://localhost
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/database/database.sqlite
```

Build the image, generate the application key inside a one-off container, and
start the stack:

```bash
docker compose build
docker compose run --rm --entrypoint composer laravel install --no-interaction
docker compose run --rm --entrypoint php laravel artisan key:generate --no-interaction
docker compose up
```

The explicit Composer step is required on a clean checkout. The development
image does not copy the bind-mounted repository or install its dependencies at
build time, and overriding the normal entrypoint for the Artisan command would
otherwise bypass its dependency installation.

Open `http://localhost`, or the host and port represented by `APP_URL` and
`APP_PORT`. The development Nginx configuration proxies Vite asset requests to
the Laravel container. Stop the foreground stack with Control-C; use
`docker compose down` after background operation.

The SQLite path must be explicit because the development entrypoint touches the
shell value of `DB_DATABASE` before Laravel applies its configuration default.
The tracked `.env.docker` is retained as historical deployment evidence, but it
is not copied into either image and is not a safe general-purpose local
template. Runtime production configuration must be injected from the deployment
environment; start from `.env.production.example` when defining those values.

See [docker-compose.yml](../../docker-compose.yml),
[the development image](../../docker/dev/Dockerfile),
[development entrypoint](../../docker/dev/start), and
[Nginx configuration](../../docker/dev/nginx.conf).

## Configuration that matters locally

- **`APP_URL`** defaults to `http://localhost:8000` and must match the browser
  origin for passkeys.
- **`APP_LOCALE`** and **`APP_FALLBACK_LOCALE`** both default to `cs`; the
  implemented interface and reachable backend/package feedback are Czech.
- **`DB_CONNECTION`** defaults to `sqlite` and uses
  `database/database.sqlite` when `DB_DATABASE` is omitted in native Laravel.
- **`SESSION_DRIVER`** defaults to `database` and requires the migrated
  `sessions` table.
- **`CACHE_STORE`** defaults to `database` and requires the migrated `cache` and
  `cache_locks` tables.
- **`QUEUE_CONNECTION`** defaults to `database` and requires the jobs migration
  plus a running worker for asynchronous work.
- **`MAIL_MAILER`** defaults to `log`, so password-reset messages are written to
  the application log rather than sent.
- **`FILESYSTEM_DISK`** defaults to `local`; files are private under
  `storage/app/private` by default.
- **`VITE_APP_NAME`** inherits `APP_NAME` and controls the browser-title suffix.

Do not derive documentation or migrations from the Laravel Boost database in
the present environment; the approved documentation specification records that
it belongs to another application. Repository migrations remain authoritative.

## Generated frontend routes

Wayfinder generates typed frontend action and route modules used by the Vue
pages. Regenerate them after changing routes or controller actions:

```bash
php artisan wayfinder:generate
```

Vite also invokes the Wayfinder plugin, and the production Docker build
explicitly generates bindings before building assets. Generated directories are
excluded from normal ESLint checks. See [vite.config.ts](../../vite.config.ts)
and [eslint.config.js](../../eslint.config.js).

## Testing and quality gates

PHP tests use PHPUnit and are split into `tests/Unit` and `tests/Feature`.
`phpunit.xml` uses in-memory SQLite plus array-backed cache, session, and mail,
so tests do not modify the development database.

Run a narrow PHP test while developing:

```bash
php artisan test --compact tests/Feature/Auth/AuthenticationTest.php
php artisan test --compact --filter=test_users_can_authenticate_using_the_login_screen
php artisan test --compact tests/Feature/Cookbook/StoreManagementTest.php
php artisan test --compact tests/Feature/Cookbook/StoreSectionManagementTest.php
```

Before finalizing PHP changes, run the project-required checks:

```bash
vendor/bin/pint --dirty --format agent
composer cs
composer phpstan
php artisan test --compact path/to/AffectedTest.php
```

`composer cs` checks Pint formatting without changing files. `composer phpstan`
runs Larastan/PHPStan at level 10. The broader `composer test` clears cached
configuration, checks formatting and types, then runs all PHP tests.

Frontend unit tests are colocated as `*.spec.ts` files and run with Vitest.
Run a narrow test while developing:

```bash
pnpm test:node --maxWorkers=1 --testTimeout=10000 resources/js/composables/useInitials.spec.ts
pnpm test:node --maxWorkers=1 --testTimeout=10000 resources/js/components/stores/StoreUi.spec.ts
pnpm test:node --maxWorkers=1 --testTimeout=10000 resources/js/components/stores/StoreSectionUi.spec.ts
```

Replace the example path with an existing affected test. Before finalizing
JavaScript, TypeScript, or Vue changes, run:

```bash
pnpm eslint
pnpm prettier
pnpm tsc
pnpm test:node --maxWorkers=1 --testTimeout=10000 path/to/affected.spec.ts
```

Run Vitest without a file path when a change has broad frontend impact. The
repository also exposes fixing scripts as `pnpm lint` and `pnpm format`; do not
confuse them with the non-mutating final checks above.

For changes spanning backend and frontend, run both groups. For frontend build
tooling, dependencies, Docker, or CI changes, also run:

```bash
pnpm install --frozen-lockfile
pnpm build
```

The exact mandatory workflow is maintained in [AGENTS.md](../../AGENTS.md).
Script definitions and test environments are in
[composer.json](../../composer.json), [package.json](../../package.json),
[phpunit.xml](../../phpunit.xml), and [phpstan.neon](../../phpstan.neon).

## Useful diagnostics

```bash
php artisan about
php artisan route:list --except-vendor
php artisan migrate:status
php artisan config:show database.default
php artisan config:show queue.default
php artisan pail
```

If frontend source changes do not appear, confirm that Vite is running through
`composer dev`, `pnpm dev`, or the Compose stack. If the application reports a
missing Vite manifest in a non-development run, rebuild assets with
`pnpm build`.

If authentication works but passkeys do not, first compare the browser origin
with `APP_URL`, then confirm that the `passkeys` migration ran. If password-reset
mail appears missing under the default configuration, inspect the application
log because `MAIL_MAILER=log` does not contact an SMTP server.
