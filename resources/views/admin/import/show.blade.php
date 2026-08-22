<x-layouts::admin :title="__('Import Details')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('admin.import') }}" wire:navigate>
                <flux:icon name="arrow-left" class="mr-2 h-4 w-4" />
                Back to Import History
            </flux:button>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-xl border border-black/5 bg-white">
                <div class="border-b border-black/5 px-6 py-4">
                    <flux:heading size="md">Import Information</flux:heading>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 gap-4">
                        <div>
                            <dt class="text-sm text-black/70">File Name</dt>
                            <dd class="mt-1 font-medium text-black">{{ $import->file_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-black/70">Uploaded By</dt>
                            <dd class="mt-1 font-medium text-black">{{ $import->uploader->name ?? 'System' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-black/70">Import Date</dt>
                            <dd class="mt-1 font-medium text-black">{{ $import->created_at->format('Y-m-d H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-black/70">Status</dt>
                            <dd class="mt-1">
                                @if($import->status === \App\Models\ParticipantImport::STATUS_COMPLETED)
                                    <flux:badge color="green">Completed</flux:badge>
                                @elseif($import->status === \App\Models\ParticipantImport::STATUS_COMPLETED_WITH_ERRORS)
                                    <flux:badge color="yellow">Completed with Errors</flux:badge>
                                @elseif($import->status === \App\Models\ParticipantImport::STATUS_FAILED)
                                    <flux:badge color="red">Failed</flux:badge>
                                @else
                                    <flux:badge color="zinc">Processing</flux:badge>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="rounded-xl border border-black/5 bg-white">
                <div class="border-b border-black/5 px-6 py-4">
                    <flux:heading size="md">Import Statistics</flux:heading>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-black/70">Total Rows</dt>
                            <dd class="mt-1 font-medium text-black">{{ $import->total_rows }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-black/70">Imported</dt>
                            <dd class="mt-1 font-medium text-green-600">{{ $import->imported_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-black/70">Updated</dt>
                            <dd class="mt-1 font-medium text-black">{{ $import->updated_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-black/70">Skipped</dt>
                            <dd class="mt-1 font-medium text-black">{{ $import->skipped_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-black/70">Duplicates</dt>
                            <dd class="mt-1 font-medium text-black">{{ $import->duplicate_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-black/70">Errors</dt>
                            <dd class="mt-1 font-medium text-red-600">{{ $import->error_count }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-layouts::admin>
