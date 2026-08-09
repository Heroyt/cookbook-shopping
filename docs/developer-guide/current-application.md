# Current Application

This chapter describes the code that exists today. Repository source, tests,
configuration, and migrations are authoritative for current behavior. The
[documentation specification](../documentation-spec.md) deliberately excludes
the unrelated database currently connected through Laravel Boost.

## Product boundary

The repository currently implements an authenticated Laravel application shell,
not the family cookbook product. Its working surfaces are:

- a public welcome page;
- login, logout, password reset, and passkey authentication;
- an authenticated dashboard whose content is still placeholder panels;
- profile editing and account deletion;
- password and passkey management;
- light, dark, and system appearance preferences; and
- the framework health endpoint at `/up`.

The application does not expose self-registration. Fortify enables password
reset and passkeys, while registration is absent from the enabled feature list.
The route definitions are split between the small application route files and
Fortify's package routes; inspect them with `php artisan route:list`. See
[the web routes](../../routes/web.php),
[settings routes](../../routes/settings.php), and
[Fortify configuration](../../config/fortify.php).

> **Planned**
>
> Family Membership, Current Family selection, Ingredients, Stores, Recipes,
> meal planning, nutrition, and Shopping List generation are approved domain
> design, but none is implemented. Do not infer their models, authorization, or
> persistence from the current authenticated shell. The canonical vocabulary
> is in [CONTEXT.md](../../CONTEXT.md), and the architectural direction is
> recorded in [ADR 0004](../adr/0004-build-a-laravel-modular-monolith.md).

## Technology baseline

The lock files are the reproducible version source. Key installed versions are:

| Concern           | Current version or target                                                                     | Evidence                                                                                                                                                                                  |
| ----------------- | --------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| PHP runtime       | Composer permits PHP 8.3 or newer; development, production, and CI images target PHP 8.5      | [Composer manifest](../../composer.json), [development image](../../docker/dev/Dockerfile), [production image](../../docker/production/Dockerfile), [Jenkins pipeline](../../Jenkinsfile) |
| Backend           | Laravel 13.24.0, Fortify 1.37.3, Inertia Laravel 3.3.1, Wayfinder 0.1.21                      | [Composer lock](../../composer.lock)                                                                                                                                                      |
| Frontend          | Vue 3.5.41, Inertia Vue 3.6.1, TypeScript 5.9.3                                               | [pnpm lock](../../pnpm-lock.yaml)                                                                                                                                                         |
| Build and styling | Vite 8.2.1, Tailwind CSS 4.3.3, shadcn-vue 2.8.2                                              | [pnpm lock](../../pnpm-lock.yaml), [Vite configuration](../../vite.config.ts)                                                                                                             |
| Test and analysis | PHPUnit through Laravel, Vitest 4.1.10, PHPStan/Larastan level 10, ESLint, Prettier, and Pint | [Composer manifest](../../composer.json), [package manifest](../../package.json), [PHPStan configuration](../../phpstan.neon)                                                             |

## Request and rendering architecture

Laravel owns routing, authentication, validation, persistence, and the Inertia
response. Inertia renders Vue page components without a separate JSON API.
`resources/views/app.blade.php` is the HTML shell, and
[`resources/js/app.ts`](../../resources/js/app.ts) boots the client and chooses
layouts by page name:

- `Welcome` has no application layout;
- `auth/*` uses the authentication layout;
- `settings/*` nests the settings layout inside the application layout; and
- every other page uses the application layout.

The application layout provides a responsive sidebar, breadcrumb header, and
toast host. The sidebar currently links only to the placeholder dashboard;
settings are reached through the user menu. See
[the sidebar](../../resources/js/components/AppSidebar.vue) and
[settings layout](../../resources/js/layouts/settings/Layout.vue).

Controllers return named Inertia pages. Vue forms use Inertia's `Form`
component and generated Wayfinder action or route functions rather than
hard-coded submission URLs. Wayfinder output lives under generated
`resources/js/actions`, `resources/js/routes`, and
`resources/js/wayfinder` directories and is regenerated with
`php artisan wayfinder:generate`. The Vite plugin is configured in
[vite.config.ts](../../vite.config.ts).

Frontend code uses Vue Single-File Components with `<script setup lang="ts">`,
the `@/` alias for `resources/js`, Tailwind utilities, and reusable shadcn-vue
components under `resources/js/components/ui`. TypeScript strict mode is
enabled. See [TypeScript configuration](../../tsconfig.json),
[ESLint configuration](../../eslint.config.js), and
[application styles](../../resources/css/app.css).

## Authentication and account behavior

Fortify uses the session-based `web` guard and the email address as the login
identifier. Login is limited to five attempts per minute per normalized email
and IP combination. Passkey operations are limited to ten attempts per minute
per credential or session and IP combination. The WebAuthn relying-party ID and
allowed origin are derived from `APP_URL`, so an incorrect URL breaks passkey
registration or login. See
[FortifyServiceProvider](../../app/Providers/FortifyServiceProvider.php) and
[Fortify configuration](../../config/fortify.php).

The `User` model implements Fortify's passkey contract. Its password is hashed
through an Eloquent cast, and credential secrets are hidden from serialization.
Profile email changes clear `email_verified_at`; account deletion logs out the
session, removes the User, and relies on the passkey foreign key's cascading
delete. Evidence is in [User.php](../../app/Models/User.php),
[ProfileController.php](../../app/Http/Controllers/Settings/ProfileController.php),
and [the passkey migration](../../database/migrations/2024_01_01_000000_create_passkeys_table.php).

`HandleInertiaRequests` shares the application name, authenticated User, and
sidebar state with every Inertia page. `HandleAppearance` shares the appearance
cookie with the root view. The appearance and sidebar cookies are deliberately
excluded from cookie encryption; neither is an authorization source. See
[application bootstrap](../../bootstrap/app.php),
[HandleInertiaRequests](../../app/Http/Middleware/HandleInertiaRequests.php),
and [HandleAppearance](../../app/Http/Middleware/HandleAppearance.php).

## Current persistence

Only infrastructure and account data are migrated. The current schema contains:

- Users, password-reset tokens, and sessions;
- passkey credentials;
- database cache entries and locks; and
- queued jobs, job batches, and failed jobs.

There are no Family, Recipe, Ingredient, Store, Calendar Entry, nutrition, or
Shopping List tables. See the [migration directory](../../database/migrations/).

The default environment uses SQLite and database-backed sessions, cache, and
queues. The local filesystem disk points to `storage/app/private`; the public
disk points to `storage/app/public`. Password-reset mail is logged by default
rather than delivered. These defaults come from
[.env.example](../../.env.example), [database configuration](../../config/database.php),
[filesystem configuration](../../config/filesystems.php), and
[mail configuration](../../config/mail.php).

The included seeder creates a single development User. It is test fixture
convenience, not an account-provisioning workflow; see
[DatabaseSeeder.php](../../database/seeders/DatabaseSeeder.php).

## Infrastructure already present

The repository includes two container paths:

- Docker Compose runs a development PHP-FPM container and Nginx, bind-mounts
  the repository, and exposes the Vite development server.
- The multi-stage production Dockerfile installs optimized Composer
  dependencies, generates Wayfinder bindings, builds Vite assets, and produces
  a PHP-FPM image. Its entrypoint links storage, runs migrations, starts the
  scheduler, and starts PHP-FPM.

The development container additionally supervises PHP-FPM, Vite, and a database
queue worker. Both images run Laravel's scheduler every minute through `crond`.
See [docker-compose.yml](../../docker-compose.yml),
[development entrypoint](../../docker/dev/start), and
[production entrypoint](../../docker/production/start).

Jenkins runs PHP quality checks and parallel tests, frontend type checks and
Vitest, and a production-image build for change requests. Commits on `main` or
`master` build and push a multi-platform image to a Scaleway registry and ask
Komodo to deploy the configured stack. This describes pipeline code, not proof
that credentials, registry access, the target stack, backups, or rollback have
been operationally verified. See [Jenkinsfile](../../Jenkinsfile).

## Known current-state gaps

- The package manifest declares pnpm 11.17.0, the development image installs
  pnpm 10.30.2, and the production image activates unpinned `pnpm@latest`.
  Reproducible local and container builds should converge on one pinned version.
- The Composer `setup` script invokes npm even though pnpm and
  `pnpm-lock.yaml` are the declared frontend package contract.
- The same `setup` script runs SQLite migrations without first creating
  `database/database.sqlite`. GitHub Actions invokes it on a clean checkout, so
  the workflow needs an explicit database-file step or an in-memory setup
  database before it can be treated as reproducible.
- The production Docker build copies `.env.docker` into the image. That file
  contains local/debug defaults and a deployment-specific URL. Operators must
  supply reviewed runtime configuration; the image should not be treated as a
  production-ready configuration artifact by itself.
- The container entrypoints tolerate migration failures and continue startup,
  which can leave application code running against an outdated schema.
- The development Compose stack defines no database service. Its default
  practical path is the repository's SQLite database, or an externally managed
  database configured in `.env`.
- Cookbook-specific authorization, isolation tests, storage strategy, backup
  policy, monitoring, and recovery are not implemented yet.

Use [Local Development](local-development.md) for setup and quality commands.
