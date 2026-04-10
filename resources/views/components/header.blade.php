@props(['header', 'description'])

<div class="text-left mb-6">
    <h1 class="text-2xl font-bold uppercase tracking-wider">{{ $header }}</h1>
    <flux:text class="mb-6 text-base">{{ $description }}</flux:text>
    <flux:separator />
</div>
