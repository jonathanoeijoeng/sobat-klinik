@props(['header', 'description'])

<div class="text-left md-4 md:mb-6">
    <flux:heading size="xl">{{ $header }}</flux:heading>
    <flux:text class="mb-6 text-base">{!! $description !!}</flux:text>
    <flux:separator />
</div>
