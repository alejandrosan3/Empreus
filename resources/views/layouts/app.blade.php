<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Empreus Group, Inc') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxStyles
        @livewireStyles
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">

        <flux:sidebar sticky stashable class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">

            <!-- Multi-Account Dropdown -->
            <livewire:layout.multiaccounts-dropdown />

            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <flux:input as="button" variant="filled" placeholder="Search..." icon="magnifying-glass" />

            <!-- horizonal toolbar + profile -->
            <livewire:layout.sidebar-navigation/>

            <flux:spacer />


            <!-- Dark Mode Toggle -->
            <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                <flux:radio value="light" icon="sun" />
                <flux:radio value="dark" icon="moon" />
                <flux:radio value="system" icon="computer-desktop" />
            </flux:radio.group>

            <flux:separator />

            <!-- App Version -->
            <flux:menu.item>v.{{ $appVersion }}</flux:menu.item>
        </flux:sidebar>

        <flux:header class="!block bg-white lg:bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
            
            <livewire:layout.navigation />
        </flux:header>

        <flux:main>
             <!-- Page Content -->
             <main>
                {{ $slot }}
            </main>
        </flux:main>

        @fluxScripts
        @livewireScripts
    </body>
</html>
