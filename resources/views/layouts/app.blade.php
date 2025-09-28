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

        @fluxAppearance
        @livewireStyles
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">

        <flux:sidebar sticky stashable class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">

            <!-- Mobile Close Button - Top Left -->
            <div class="lg:hidden flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm" class="text-zinc-700 dark:text-zinc-300">Menu</flux:heading>
                <flux:sidebar.toggle icon="x-mark" />
            </div>

            <!-- Multi-Account Dropdown -->
            <livewire:layout.multiaccounts-dropdown />


            <!-- Navigation Menu -->
            <livewire:layout.sidebar-navigation/>

            <flux:spacer />

            <div class="flex items-center justify-between w-full p-2">

                <!-- Settings Button -->

                <!-- Dark Mode Toggle -->
                <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                    <flux:radio value="light" icon="sun" />
                    <flux:radio value="dark" icon="moon" />
                </flux:radio.group>

                <!-- Profile section -->
                <flux:dropdown position="top" align="start">
                    <flux:profile avatar="https://fluxui.dev/img/demo/user.png" />

                    <flux:menu>
                        <flux:menu.item icon="user-circle" :href="route('profile')">Profile</flux:menu.item>
                        <flux:menu.item icon="cog-8-tooth" :href="route('profile')">Settings</flux:menu.item>

                        <flux:menu.separator />
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle">Logout</flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>

            <flux:separator />

            <!-- App Version -->
            <flux:menu.item>v.{{ $appVersion }}</flux:menu.item>
        </flux:sidebar>


        @php
            $showFilters = $showFilters ?? false;
            $showSearch  = $showSearch  ?? false;
            $showColumns  = $showColumns  ?? false;
            $showAddNew = $showAddNew ?? false;
            $currentAddable = $currentAddable ?? null;
        @endphp

        <!--  Horizontal Navbar -->
        <flux:header class="block! bg-white lg:bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
            <!-- Mobile Header -->
            <div class="lg:hidden flex items-center justify-between p-4">
                <div class="flex items-center">
                    <flux:sidebar.toggle icon="bars-3" class="mr-3" />
                    <flux:heading size="lg" class="text-zinc-900 dark:text-white">
                        {{ config('app.name', 'Empreus') }}
                    </flux:heading>
                </div>
                <div class="flex items-center space-x-2">
                    <!-- Dark Mode Toggle for Mobile -->
                    <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" class="scale-75">
                        <flux:radio value="light" icon="sun" />
                        <flux:radio value="dark" icon="moon" />
                    </flux:radio.group>
                    
                    <!-- Profile Dropdown for Mobile -->
                    <flux:dropdown position="bottom" align="end">
                        <flux:profile avatar="https://fluxui.dev/img/demo/user.png" />
                        <flux:menu>
                            <flux:menu.item icon="user-circle" :href="route('profile')">Profile</flux:menu.item>
                            <flux:menu.item icon="cog-8-tooth" :href="route('profile')">Settings</flux:menu.item>
                            <flux:menu.separator />
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle">Logout</flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
            
            <!-- Desktop Navigation -->
            <div class="hidden lg:block">
                <livewire:layout.navigation
                    :showFilters="$showFilters"
                    :showSearch="$showSearch"
                    :showAddNew="$showAddNew"
                    :showColumns="$showColumns"
                    :currentAddable="$currentAddable"
                />
            </div>
        </flux:header>

        <flux:main>
             <!-- Page Content -->
             <main>
                {{ $slot }}
            </main>
        </flux:main>

        @fluxScripts
        @livewireScriptConfig

        {{-- Main Persistent. there's no need to add it anywhere else in the app --}}
        @persist('toast')
            <flux:toast />
        @endpersist
    </body>
</html>
