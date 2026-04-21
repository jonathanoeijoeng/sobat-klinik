@props([
    'sidebar' => false,
])
@php
    if (auth()->check()) {
        $clinic_id = Auth::user()->clinic_id;
        $clinic = App\Models\Clinic::find($clinic_id);
        $clinic_name = $clinic->name;
        $logo = asset('storage/logo/' . $clinic->logo);
    }
@endphp

@if ($sidebar)
    <flux:sidebar.brand name="{{ $clinic_name }}" {{ $attributes }}>
        <x-slot name="logo" class="flex items-center justify-center h-10 w-10">
            <img src="{{ $logo }}" alt="{{ config('app.name') }}" class="w-10 h-10" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Laravel Starter Kit" {{ $attributes }}>
        <x-slot name="logo" class="flex items-center justify-center h-10 w-10">
            <img src="{{ $logo }}" alt="{{ config('app.name') }}" class="w-10 h-10" />
        </x-slot>
    </flux:brand>
@endif
