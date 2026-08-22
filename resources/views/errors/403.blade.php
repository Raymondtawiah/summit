<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col items-center gap-6">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-red-100 dark:bg-red-900/30">
                    <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                </div>
                <flux:heading size="xl">Unauthorized</flux:heading>
                <flux:text class="text-center text-zinc-600 dark:text-zinc-400">
                    You do not have permission to access this page. Please contact your administrator if you believe this is an error.
                </flux:text>
                <flux:button variant="primary" href="{{ route('home') }}" wire:navigate>
                    Return to Home
                </flux:button>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
