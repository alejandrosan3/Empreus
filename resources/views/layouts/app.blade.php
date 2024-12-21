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
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">

        <flux:sidebar sticky stashable class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <flux:brand href="#" logo="https://fluxui.dev/img/demo/logo.png" name="Acme Inc." class="px-2 dark:hidden" />
            <flux:brand href="#" logo="https://fluxui.dev/img/demo/dark-mode-logo.png" name="Acme Inc." class="px-2 hidden dark:flex" />

            <flux:input as="button" variant="filled" placeholder="Search..." icon="magnifying-glass" />

            <livewire:layout.sidebar-navigation/>

            <flux:spacer />

            <flux:menu.item>v.0.1</flux:menu.item>
        </flux:sidebar>

        <flux:header class="!block bg-white lg:bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
            
            <livewire:layout.navigation />
        </flux:header>

        <flux:main>
            <flux:heading size="xl" level="1">Good afternoon, Olivia</flux:heading>

            <flux:subheading size="lg" class="mb-6">Here's what's new today</flux:subheading>

            <flux:separator variant="subtle" />

             <!-- Page Content -->
             <main>
                {{ $slot }}
            </main>
        </flux:main>

        @fluxScripts
    </body>
</html>
