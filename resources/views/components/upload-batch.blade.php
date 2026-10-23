<div x-data="{ uploadError: '' }"
    x-on:change.capture="
        uploadError = '';
        if ($event.target.type === 'file' && Array.from($event.target.files || []).reduce((sum, file) => sum + file.size, 0) > 60 * 1024 * 1024) {
            $event.stopImmediatePropagation();
            $event.target.value = '';
            $wire.set($event.target.getAttribute('wire:model'), []);
            uploadError = 'Select at most 60 MB at a time. Upload larger collections in separate batches.';
        }
    ">
    {{ $slot }}
    <p style="font-size:.72rem;margin:.3rem 0;">Maximum 60 MB per batch.</p>
    <p x-cloak x-show="uploadError" x-text="uploadError" role="alert" style="color:#dc2626;font-size:.76rem;"></p>
</div>
