<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('Welcome') }} - {{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @vite(['resources/css/app.css'])
    </head>
    <body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex min-h-screen flex-col">

        <header class="bg-white dark:bg-slate-900 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <img class="h-8 w-auto" src="{{ asset('logo.png') }}" alt="Camp Meeting Summit">
                        </div>
                        <div class="hidden md:block">
                            <div class="ml-10 flex items-baseline space-x-4">
                                @auth
                                    <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-md text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-indigo-500">{{ __('Dashboard') }}</a>
                                @else
<a href="{{ route('login') }}" class="px-3 py-2 rounded-md text-sm font-medium text-indigo-600 dark:text-indigo-200 hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-indigo-500">{{ __('Log in') }}</a>
                                 @if (Route::has('register'))
                                     <a href="{{ route( 'register') }}" class="ml-4 px-4 py-2 rounded-md text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 shadow-sm shadow-indigo-600/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-indigo-500 transition-all">{{ __('Register') }}</a>
                                     @endif
                                @endauth
                            </div>
                        </div>
                    </div>
                    <div class="hidden md:block">
                        @auth
                            <div class="ml-4 flex items-baseline space-x-4">
                                <a href="{{ route('profile') }}" class="px-3 py-2 rounded-md text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-slate-400">{{ __('Profile') }}</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="px-3 py-2 rounded-md text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-slate-400">
                                        {{ __('Log out') }}
                                    </button>
                                </form>
                            </div>
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <div class="max-w-7xl mx-auto py-12 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-xl shadow-slate-900/5 sm:rounded-2xl ring-1 ring-slate-900/5 dark:ring-white/10">
                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">

                        <!-- Text content -->
                        <div class="p-8 lg:p-12 flex flex-col justify-center">
                            <span class="inline-flex items-center gap-2 text-xs font-semibold tracking-widest uppercase text-indigo-600 dark:text-indigo-400 mb-5">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
                                Camp Meeting 2026
                            </span>

                            <h1 class="text-3xl lg:text-4xl font-extrabold text-slate-900 dark:text-white mb-6 leading-tight">
                                Welcome to the
                                <span class="bg-gradient-to-r from-indigo-600 via-purple-600 to-blue-600 bg-clip-text text-transparent">Summit</span>
                            </h1>

                            <p class="mb-8 text-lg text-slate-600 dark:text-slate-400 leading-relaxed">
                                Join thousands of believers for a life-changing experience of worship, fellowship, and spiritual growth at our annual camp meeting.
                            </p>

                            <div class="space-y-3">
                                <a href="{{ route('login') }}" class="w-full inline-flex justify-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg shadow-lg shadow-indigo-600/25 hover:shadow-xl hover:shadow-indigo-600/30 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-indigo-500 transition-all duration-200">
                                    {{ __('Log in') }}
                                </a>
                                @if (Route::has('register'))
<a href="{{ route('register') }}" class="w-full inline-flex justify-center px-6 py-3 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-200 font-semibold rounded-lg shadow-sm border border-indigo-200 dark:border-indigo-900/10 hover:bg-indigo-100 dark:hover:bg-indigo-800/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white focus:ring-indigo-500 transition-colors duration-200">
                                         {{ __('Register') }}
                                     </a>
                                @endif
                            </div>
                        </div>

                        <!-- Image/Illustration -->
                        <div class="hidden lg:flex bg-gradient-to-br from-indigo-600 via-purple-600 to-blue-600 items-center justify-center p-8 relative overflow-hidden">
                            <div class="pointer-events-none absolute -top-16 -left-16 w-72 h-72 bg-white/10 rounded-full blur-3xl"></div>
                            <div class="pointer-events-none absolute -bottom-20 -right-10 w-72 h-72 bg-blue-300/20 rounded-full blur-3xl"></div>

                            <div class="relative text-center">
                                <div class="h-96 w-96 rounded-2xl bg-white/95 dark:bg-slate-900/90 backdrop-blur p-8 shadow-2xl flex flex-col items-center justify-center">
                                    <svg class="h-40 w-40 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 72 72" stroke="currentColor" stroke-width="2">
                                        <path d="M12 6a6 6 0 016-6h24a6 6 0 016 6v12a6 6 0 01-6 6h-6a18 18 0 00-18 18v6a6 6 0 01-12 0v-6a18 18 0 00-18-18h-6a6 6 0 01-6-6V6z" />
                                        <path d="M12 18a4 4 0 00-4 4v6a4 4 0 004 4h12a4 4 0 004-4v-6a4 4 0 00-4-4H12z" />
                                        <path d="M36 10a6 6 0 016-6h12a6 6 0 016 6v36a6 6 0 01-6 6h-12a6 6 0 01-6-6v-6z" />
                                    </svg>
                                    <p class="mt-6 text-sm font-medium text-slate-500 dark:text-slate-400 text-center">
                                        Camp Meeting 2026 &bull; July 10&ndash;15 &bull; Forest Retreat Center
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </main>

        <footer class="bg-indigo-950 text-indigo-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex flex-col sm:flex-row sm:justify-between">
                    <span class="text-sm">&copy; {{ date('Y') }} Summit. All rights reserved.</span>
                    <div class="space-x-4 mt-4 sm:mt-0">
                        <a href="#" class="text-sm text-indigo-300 hover:text-white transition-colors">Privacy Policy</a>
                        <a href="#" class="text-sm text-indigo-300 hover:text-white transition-colors">Terms of Service</a>
                    </div>
                </div>
            </div>
        </footer>

    </body>
</html>
