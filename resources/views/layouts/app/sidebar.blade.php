<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible="mobile"
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        {{-- Pasien --}}
        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('MENU')" class="grid">

                <flux:sidebar.item :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"
                            fill="currentColor">
                            <path
                                d="M19 21H5C4.44772 21 4 20.5523 4 20V11L1 11L11.3273 1.6115C11.7087 1.26475 12.2913 1.26475 12.6727 1.6115L23 11L20 11V20C20 20.5523 19.5523 21 19 21ZM6 19H18V9.15745L12 3.7029L6 9.15745V19ZM9 9.99998H15V16H9V9.99998ZM11 12V14H13V12H11Z">
                            </path>
                        </svg>
                    </x-slot>
                    {{ __('Dashboard') }}

                </flux:sidebar.item>

                <flux:sidebar.item :href="route('patients.index')" :current="request()->routeIs('patients.index')"
                    wire:navigate>
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"
                            fill="currentColor">
                            <path
                                d="M12 11C14.7614 11 17 13.2386 17 16V22H15V16C15 14.4023 13.7511 13.0963 12.1763 13.0051L12 13C10.4023 13 9.09634 14.2489 9.00509 15.8237L9 16V22H7V16C7 13.2386 9.23858 11 12 11ZM5.5 14C5.77885 14 6.05009 14.0326 6.3101 14.0942C6.14202 14.594 6.03873 15.122 6.00896 15.6693L6 16L6.0007 16.0856C5.88757 16.0456 5.76821 16.0187 5.64446 16.0069L5.5 16C4.7203 16 4.07955 16.5949 4.00687 17.3555L4 17.5V22H2V17.5C2 15.567 3.567 14 5.5 14ZM18.5 14C20.433 14 22 15.567 22 17.5V22H20V17.5C20 16.7203 19.4051 16.0796 18.6445 16.0069L18.5 16C18.3248 16 18.1566 16.03 18.0003 16.0852L18 16C18 15.3343 17.8916 14.694 17.6915 14.0956C17.9499 14.0326 18.2211 14 18.5 14ZM5.5 8C6.88071 8 8 9.11929 8 10.5C8 11.8807 6.88071 13 5.5 13C4.11929 13 3 11.8807 3 10.5C3 9.11929 4.11929 8 5.5 8ZM18.5 8C19.8807 8 21 9.11929 21 10.5C21 11.8807 19.8807 13 18.5 13C17.1193 13 16 11.8807 16 10.5C16 9.11929 17.1193 8 18.5 8ZM5.5 10C5.22386 10 5 10.2239 5 10.5C5 10.7761 5.22386 11 5.5 11C5.77614 11 6 10.7761 6 10.5C6 10.2239 5.77614 10 5.5 10ZM18.5 10C18.2239 10 18 10.2239 18 10.5C18 10.7761 18.2239 11 18.5 11C18.7761 11 19 10.7761 19 10.5C19 10.2239 18.7761 10 18.5 10ZM12 2C14.2091 2 16 3.79086 16 6C16 8.20914 14.2091 10 12 10C9.79086 10 8 8.20914 8 6C8 3.79086 9.79086 2 12 2ZM12 4C10.8954 4 10 4.89543 10 6C10 7.10457 10.8954 8 12 8C13.1046 8 14 7.10457 14 6C14 4.89543 13.1046 4 12 4Z">
                            </path>
                        </svg>
                    </x-slot>

                    {{ __('Pasien') }}
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('out-patients.index')"
                    :current="request()->routeIs('out-patients.index')" wire:navigate>
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20"
                            fill="currentColor">
                            <path
                                d="M8 20V14H16V20H19V4H5V20H8ZM10 20H14V16H10V20ZM21 20H23V22H1V20H3V3C3 2.44772 3.44772 2 4 2H20C20.5523 2 21 2.44772 21 3V20ZM11 8V6H13V8H15V10H13V12H11V10H9V8H11Z">
                            </path>
                        </svg>
                    </x-slot>

                    {{ __('Rawat Jalan') }}
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('practitioner.index')"
                    :current="request()->routeIs('practitioner.index')" wire:navigate>
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20"
                            height="20">
                            <path
                                d="M8 3V5H6V9C6 11.2091 7.79086 13 10 13C12.2091 13 14 11.2091 14 9V5H12V3H15C15.5523 3 16 3.44772 16 4V9C16 11.9727 13.8381 14.4405 11.0008 14.9169L11 16.5C11 18.433 12.567 20 14.5 20C15.9973 20 17.275 19.0598 17.7749 17.7375C16.7283 17.27 16 16.2201 16 15C16 13.3431 17.3431 12 19 12C20.6569 12 22 13.3431 22 15C22 16.3711 21.0802 17.5274 19.824 17.8854C19.2102 20.252 17.0592 22 14.5 22C11.4624 22 9 19.5376 9 16.5L9.00019 14.9171C6.16238 14.4411 4 11.9731 4 9V4C4 3.44772 4.44772 3 5 3H8Z">
                            </path>
                        </svg>
                    </x-slot>

                    {{ __('Dokter') }}
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('pharmacy.index')" :current="request()->routeIs('pharmacy*')"
                    wire:navigate>
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20"
                            height="20">
                            <path
                                d="M19.7786 4.22184C22.1217 6.56498 22.1217 10.364 19.7786 12.7071L12.7075 19.7782C10.3643 22.1213 6.56535 22.1213 4.2222 19.7782C1.87906 17.435 1.87906 13.6361 4.2222 11.2929L11.2933 4.22184C13.6364 1.87869 17.4354 1.87869 19.7786 4.22184ZM14.1224 15.5348L8.46555 9.87798L5.63642 12.7071C4.07432 14.2692 4.07432 16.8019 5.63642 18.364C7.19851 19.9261 9.73117 19.9261 11.2933 18.364L14.1224 15.5348ZM18.3643 5.63605C16.8022 4.07395 14.2696 4.07395 12.7075 5.63605L9.87976 8.46377L15.5366 14.1206L18.3643 11.2929C19.9264 9.73081 19.9264 7.19815 18.3643 5.63605Z">
                            </path>
                        </svg>
                    </x-slot>

                    {{ __('Farmasi') }}
                </flux:sidebar.item>

                <flux:sidebar.item :href="route('cashier.index')" :current="request()->routeIs('cashier*')"
                    wire:navigate>
                    <x-slot name="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20"
                            height="20">
                            <path
                                d="M3.00488 3.00275H21.0049C21.5572 3.00275 22.0049 3.45046 22.0049 4.00275V20.0027C22.0049 20.555 21.5572 21.0027 21.0049 21.0027H3.00488C2.4526 21.0027 2.00488 20.555 2.00488 20.0027V4.00275C2.00488 3.45046 2.4526 3.00275 3.00488 3.00275ZM4.00488 5.00275V19.0027H20.0049V5.00275H4.00488ZM8.50488 14.0027H14.0049C14.281 14.0027 14.5049 13.7789 14.5049 13.5027C14.5049 13.2266 14.281 13.0027 14.0049 13.0027H10.0049C8.62417 13.0027 7.50488 11.8835 7.50488 10.5027C7.50488 9.12203 8.62417 8.00275 10.0049 8.00275H11.0049V6.00275H13.0049V8.00275H15.5049V10.0027H10.0049C9.72874 10.0027 9.50488 10.2266 9.50488 10.5027C9.50488 10.7789 9.72874 11.0027 10.0049 11.0027H14.0049C15.3856 11.0027 16.5049 12.122 16.5049 13.5027C16.5049 14.8835 15.3856 16.0027 14.0049 16.0027H13.0049V18.0027H11.0049V16.0027H8.50488V14.0027Z">
                            </path>
                        </svg>
                    </x-slot>

                    {{ __('Kasir') }}
                </flux:sidebar.item>

            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:spacer />

        <flux:sidebar.nav>
            <div x-data="{ open: @js(request()->routeIs('settings.*')) }">
                <flux:sidebar.item icon="adjustments-vertical" @click.prevent="open = !open" class="cursor-pointer"
                    :current="request()->routeIs('settings.*')">
                    <div class="flex items-center justify-between w-full">
                        <span>{{ __('Settings') }}</span>
                        <flux:icon name="chevron-down" variant="micro" class="transition-transform duration-200"
                            x-bind:class="open ? 'rotate-180' : ''" />
                    </div>
                </flux:sidebar.item>

                <div x-show="open" x-collapse class="pl-10 mt-1 space-y-1">
                    <flux:sidebar.item :href="route('settings.master-clinics')"
                        :current="request()->routeIs('settings.master-clinics')" wire:navigate size="sm">
                        {{ __('Master Data Clinic') }}
                    </flux:sidebar.item>
                </div>
                <div x-show="open" x-collapse class="pl-10 mt-1 space-y-1">
                    <flux:sidebar.item :href="route('settings.master-obat')"
                        :current="request()->routeIs('settings.master-obat')" wire:navigate size="sm">
                        {{ __('Master Data Obat') }}
                    </flux:sidebar.item>
                </div>
            </div>

            <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire"
                target="_blank">
                {{ __('Documentation') }}
            </flux:sidebar.item>
        </flux:sidebar.nav>

        <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

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
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer" data-test="logout-button">
                        {{ __('Log out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist
    <x-toaster />
    @fluxScripts
</body>

</html>
