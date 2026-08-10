# Current Application

This chapter describes the code that exists today. Repository source, tests,
configuration, and migrations are authoritative for current behavior. The
[documentation specification](../documentation-spec.md) deliberately excludes
the unrelated database currently connected through Laravel Boost.

## Product boundary

The repository currently implements an authenticated Laravel application shell
and the first narrow Family Access workflow. Its working surfaces are:

- a public welcome page;
- login, logout, password reset, and passkey authentication;
- an authenticated dashboard whose content is still placeholder panels;
- Family creation, which creates the first roleless Family Membership for the
  authenticated User;
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
> Membership management beyond the initial membership, Current Family
> selection, Ingredients, Stores, Recipes, meal planning, nutrition, and
> Shopping List generation remain approved domain design rather than available
> behavior. Do not infer their models, authorization, or persistence from the
> Family-creation tracer. The canonical vocabulary is in
> [CONTEXT.md](../../CONTEXT.md), and the architectural direction is recorded in
> [ADR 0004](../adr/0004-build-a-laravel-modular-monolith.md).

## Technology baseline

The lock files are the reproducible version source. Key installed versions are:

- **PHP runtime:** Composer permits PHP 8.3 or newer; development, production,
  and CI images target PHP 8.5. See the
  [Composer manifest](../../composer.json),
  [development image](../../docker/dev/Dockerfile),
  [production image](../../docker/production/Dockerfile), and
  [Jenkins pipeline](../../Jenkinsfile).
- **Backend:** Laravel 13.24.0, Fortify 1.37.3, Inertia Laravel 3.3.1, and
  Wayfinder 0.1.21. See the [Composer lock](../../composer.lock).
- **Frontend:** Vue 3.5.41, Inertia Vue 3.6.1, and TypeScript 5.9.3. See the
  [pnpm lock](../../pnpm-lock.yaml).
- **Build and styling:** Vite 8.2.1, Tailwind CSS 4.3.3, and shadcn-vue 2.8.2.
  See the [pnpm lock](../../pnpm-lock.yaml) and
  [Vite configuration](../../vite.config.ts).
- **Test and analysis:** PHPUnit through Laravel, Vitest 4.1.10,
  PHPStan/Larastan level 10, ESLint, Prettier, and Pint. See the
  [Composer manifest](../../composer.json),
  [package manifest](../../package.json), and
  [PHPStan configuration](../../phpstan.neon).

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
toast host. The sidebar links to the placeholder dashboard and the Family
creation page; settings are reached through the user menu. See
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
Profile email changes clear `email_verified_at`. Account deletion is rejected
if the User is the final member of any Family. Otherwise it clears the remember
token, removes the User, cascades that User's Family Memberships and passkeys,
and logs out the session. Evidence is in [User.php](../../app/Models/User.php),
[ProfileController.php](../../app/Http/Controllers/Settings/ProfileController.php),
[DeleteUser.php](../../app/FamilyAccess/Actions/DeleteUser.php),
and [the passkey migration](../../database/migrations/2024_01_01_000000_create_passkeys_table.php).

`HandleInertiaRequests` shares the application name, authenticated User, and
sidebar state with every Inertia page. `HandleAppearance` shares the appearance
cookie with the root view. The appearance and sidebar cookies are deliberately
excluded from cookie encryption; neither is an authorization source. See
[application bootstrap](../../bootstrap/app.php),
[HandleInertiaRequests](../../app/Http/Middleware/HandleInertiaRequests.php),
and [HandleAppearance](../../app/Http/Middleware/HandleAppearance.php).

## Current persistence

Infrastructure, account data, and the first Family Access records are migrated.
The current schema contains:

- Users, password-reset tokens, and sessions;
- passkey credentials;
- Families and unique roleless Family Memberships;
- database cache entries and locks; and
- queued jobs, job batches, and failed jobs.

There are no Current Family preference, Recipe, Ingredient, Store, Calendar
Entry, nutrition, or Shopping List tables. See the
[migration directory](../../database/migrations/).

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
Vitest, and a production-image build for change requests. When Jenkins runs the
pipeline for `main` or `master`, its branch-conditioned stages build and push a
multi-platform image to a Scaleway registry and ask Komodo to deploy the
configured stack. This describes pipeline code, not proof that external job
triggers, status gates, credentials, registry access, the target stack, health
polling, migrations, or persistence across container recreation have been
operationally verified. See [Jenkinsfile](../../Jenkinsfile).

## Known current-state constraints and evidence gaps

- The development Compose stack defines no database service. Its default
  path is the repository's SQLite database. The user attests that production
  MariaDB runs on the application host, but its live configuration is not
  represented in this repository.
- The production image contains PHP-FPM and a scheduler trigger but no web
  proxy or queue worker. The external Komodo stack supplies the single-container
  runtime by user attestation; the repository uses synchronous production jobs
  and recommends `/up` as its shallow health signal, but cannot verify live
  proxy or health-check configuration.
- Runtime configuration is injected rather than baked into the production
  image, but the deployment platform's secret definitions and validated
  production values are not committed here.
- Family-owned cookbook authorization, cross-Family isolation helpers, and
  media-upload workflows are not implemented. The selected personal profile
  uses the private local filesystem and intentionally requires no automated
  backup, recovery, or centralized observability; the persistent mount remains
  external and unverified.

See [ADR 0006](../adr/0006-use-a-single-host-personal-production-profile.md)
for the selected profile and its reassessment triggers.

Use [Local Development](local-development.md) for setup and quality commands.
