<!DOCTYPE html>
<html lang="cs" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Povolit přístup agentovi') }} – {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-full bg-background font-sans text-foreground antialiased">
<main class="flex min-h-screen items-center justify-center p-4 sm:p-6">
    <section class="flex w-full max-w-lg flex-col gap-6 rounded-xl border bg-card p-6 text-card-foreground shadow-sm sm:p-8" aria-labelledby="authorization-title">
        <header class="flex flex-col gap-2 text-center">
            <h1 id="authorization-title" class="text-2xl font-semibold tracking-tight">{{ __('Povolit přístup agentovi') }}</h1>
            <p class="text-sm text-muted-foreground">
                {{ __('Aplikace :client žádá o připojení k vašemu účtu.', ['client' => $client->name]) }}
            </p>
        </header>

        <div class="flex flex-col gap-2 rounded-lg border bg-muted/50 p-4">
            <p class="text-sm text-muted-foreground">{{ __('Přihlášený účet') }}</p>
            <p class="font-medium">{{ $user->email }}</p>
            <p class="text-sm text-muted-foreground">{{ __('Rodina pro toto připojení') }}</p>
            <p class="font-medium">{{ $family?->name ?? __('Žádná dostupná rodina') }}</p>
        </div>

        <p class="text-sm text-muted-foreground">
            {{ __('Připojení zůstane omezené na tuto rodinu, i když později změníte aktuální rodinu.') }}
        </p>

        @if($errors->any())
            <div class="flex flex-col gap-2 rounded-lg border border-destructive/40 bg-destructive/10 p-4 text-sm text-destructive" role="alert">
                <p class="font-medium">{{ __('Přístup se nepodařilo povolit.') }}</p>
                <ul class="flex list-disc flex-col gap-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('passport.authorizations.approve') }}" class="flex flex-col gap-5">
            @csrf
            <input type="hidden" name="auth_token" value="{{ $authToken }}">

            <fieldset class="flex flex-col gap-3" @disabled($family === null)>
                <legend class="mb-2 font-medium">{{ __('Oprávnění') }}</legend>

                <div class="flex items-start gap-3 rounded-md border p-3">
                    <input id="content-read" type="checkbox" checked disabled class="mt-0.5 size-4 rounded border-input">
                    <label for="content-read" class="flex flex-col gap-1">
                        <span class="text-sm font-medium">{{ __('Čtení obsahu') }}</span>
                        <span class="text-sm text-muted-foreground">{{ __('Agent může prohlížet obsah zvolené rodiny.') }}</span>
                    </label>
                </div>

                @foreach([
                    \App\AgentIntegration\AgentCredentialAbility::CookbookWrite->value => [__('Úpravy kuchařky'), __('Obchody, části obchodů, suroviny, štítky a recepty.')],
                    \App\AgentIntegration\AgentCredentialAbility::PlanningWrite->value => [__('Úpravy kalendáře'), __('Přidávání a úpravy jídel v kalendáři.')],
                    \App\AgentIntegration\AgentCredentialAbility::DestructiveWrite->value => [__('Archivace a mazání'), __('Archivace obsahu a mazání historie změn.')],
                ] as $ability => [$label, $description])
                    <div class="flex items-start gap-3 rounded-md border p-3">
                        <input id="ability-{{ $loop->index }}" name="abilities[]" value="{{ $ability }}" type="checkbox" class="mt-0.5 size-4 rounded border-input">
                        <label for="ability-{{ $loop->index }}" class="flex flex-col gap-1">
                            <span class="text-sm font-medium">{{ $label }}</span>
                            <span class="text-sm text-muted-foreground">{{ $description }}</span>
                        </label>
                    </div>
                @endforeach
            </fieldset>

            <div>
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50" @disabled($family === null)>
                    {{ __('Povolit přístup') }}
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('passport.authorizations.deny') }}">
            @csrf
            @method('DELETE')
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button type="submit" class="inline-flex h-10 w-full items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                {{ __('Zamítnout') }}
            </button>
        </form>

        <p class="text-xs text-muted-foreground">
            {{ __('Připojení můžete kdykoli odvolat na stránce Přístupy agentů.') }}
        </p>
    </section>
</main>
</body>
</html>
