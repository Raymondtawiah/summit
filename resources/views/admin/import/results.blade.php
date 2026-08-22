<x-layouts::admin :title="__('Import Results')">
    <div class="flex flex-col gap-6 p-6">
        <div>
            <flux:heading size="xl">Import Complete</flux:heading>
            <flux:text class="mt-1 text-black/70">
                The participant import has finished processing.
            </flux:text>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-black/5 bg-white p-6">
                <flux:text class="text-sm text-black/70">Total Rows</flux:text>
                <flux:heading size="2xl" class="mt-2">{{ $import->total_rows }}</flux:heading>
            </div>
            <div class="rounded-xl border border-green-600/20 bg-green-600/5 p-6">
                <flux:text class="text-sm text-black/70">Successfully Imported</flux:text>
                <flux:heading size="2xl" class="mt-2 text-green-600">{{ $import->imported_count }}</flux:heading>
            </div>
            <div class="rounded-xl border border-red-600/20 bg-red-600/5 p-6">
                <flux:text class="text-sm text-black/70">Errors</flux:text>
                <flux:heading size="2xl" class="mt-2 text-red-600">{{ $import->error_count }}</flux:heading>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-sm text-black/70">Updated</flux:text>
                <flux:heading size="lg" class="mt-1">{{ $import->updated_count }}</flux:heading>
            </div>
            <div class="rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-sm text-black/70">Skipped</flux:text>
                <flux:heading size="lg" class="mt-1">{{ $import->skipped_count }}</flux:heading>
            </div>
            <div class="rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-sm text-black/70">Duplicates</flux:text>
                <flux:heading size="lg" class="mt-1">{{ $import->duplicate_count }}</flux:heading>
            </div>
            <div class="rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-sm text-black/70">Status</flux:text>
                <div class="mt-1">
                    @if($import->status === \App\Models\ParticipantImport::STATUS_COMPLETED)
                        <flux:badge color="green">Completed</flux:badge>
                    @elseif($import->status === \App\Models\ParticipantImport::STATUS_COMPLETED_WITH_ERRORS)
                        <flux:badge color="yellow">Completed with Errors</flux:badge>
                    @else
                        <flux:badge color="red">Failed</flux:badge>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="border-b border-black/5 px-6 py-4">
                <flux:heading size="lg">Import Details</flux:heading>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <dt class="text-sm text-black/70">File Name</dt>
                        <dd class="mt-1 font-medium text-black">{{ $import->file_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-black/70">Imported By</dt>
                        <dd class="mt-1 font-medium text-black">{{ $import->uploader->name ?? 'System' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-black/70">Import Date</dt>
                        <dd class="mt-1 font-medium text-black">{{ $import->created_at->format('Y-m-d H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-black/70">Completed At</dt>
                        <dd class="mt-1 font-medium text-black">{{ $import->completed_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="primary" href="{{ route('admin.participants') }}" wire:navigate>
                View Participants
            </flux:button>
            <flux:button variant="ghost" href="{{ route('admin.import') }}" wire:navigate>
                Import Another File
            </flux:button>
        </div>
    </div>
</x-layouts::admin>
