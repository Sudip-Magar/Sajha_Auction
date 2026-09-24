{{--
    A reusable Mary UI modal that replaces browser-native wire:confirm
    popups project-wide. The Livewire component owns a boolean property
    (wireModel) that opens/closes it and a method (confirmClick) that runs
    when "Confirm" is clicked; the slot is the confirmation message.
--}}
@props([
    'wireModel',
    'title' => 'Are you sure?',
    'confirmClick',
    'confirmLabel' => 'Confirm',
    'confirmClass' => 'btn-error',
    'cancelLabel' => 'Cancel',
])

<x-modal wire:model="{{ $wireModel }}" :title="$title" separator class="backdrop-blur-sm">
    <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">{{ $slot }}</p>

    <x-slot:actions>
        <x-button :label="$cancelLabel" @click="$wire.{{ $wireModel }} = false" class="btn-ghost" />
        <x-button :label="$confirmLabel" wire:click="{{ $confirmClick }}" class="{{ $confirmClass }}" spinner="{{ $confirmClick }}" />
    </x-slot:actions>
</x-modal>
