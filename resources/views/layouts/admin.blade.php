<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-white">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('admin.dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Summit Admin')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('admin.participants')" :current="request()->routeIs('admin.participants')" wire:navigate>
                        {{ __('Participants') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-up-tray" :href="route('admin.import')" :current="request()->routeIs('admin.import')" wire:navigate>
                        {{ __('Import Excel') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="ticket" :href="route('admin.tickets')" :current="request()->routeIs('admin.tickets')" wire:navigate>
                        {{ __('Tickets') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="printer" :href="route('admin.tickets')" :current="request()->routeIs('admin.tickets')" wire:navigate>
                        {{ __('Print Tickets') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('admin.staff')" :current="request()->routeIs('admin.staff')" wire:navigate>
                        {{ __('Staff') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="map-pin" :href="route('admin.scan-points')" :current="request()->routeIs('admin.scan-points')" wire:navigate>
                        {{ __('Scan Points') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-check" :href="route('admin.attendance')" :current="request()->routeIs('admin.attendance')" wire:navigate>
                        {{ __('Attendance') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chart-bar" :href="route('admin.reports.attendance')" :current="request()->routeIs('admin.reports.*')" wire:navigate>
                        {{ __('Reports') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('admin.reports.audit-logs')" :current="request()->routeIs('admin.reports.*')" wire:navigate>
                        {{ __('Audit Logs') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="cog" :href="route('profile.edit')" :current="request()->routeIs('profile.edit') || request()->routeIs('appearance.edit') || request()->routeIs('security.edit')" wire:navigate>
                        {{ __('Settings') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="user" :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>
                    {{ __('Profile') }}
                </flux:sidebar.item>
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:sidebar.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                        {{ __('Log out') }}
                    </flux:sidebar.item>
                </form>
            </flux:sidebar.nav>

            <!-- Mobile User Menu -->
            <flux:header class="lg:hidden">
                <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
                <flux:spacer />
                <flux:dropdown position="top" align="end">
                    <flux:profile
                        :initials="auth()->user()->initials()"
                        icon-trailing="chevron-down"
                    />
                    <flux:menu>
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <flux:avatar
                                        :name="auth()->user()->name"
                                        :initials="auth()->user()->initials()"
                                    />
                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                        <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>
                        <flux:menu.separator />
                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                {{ __('Settings') }}
                            </flux:menu.item>
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                                    {{ __('Log out') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu.radio.group>
                    </flux:menu>
                </flux:dropdown>
            </flux:header>
        </flux:sidebar>

        <flux:main>
            {{ $slot }}
        </flux:main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
