<x-layouts::admin :title="__('Import Participants')">
    <div class="flex flex-col gap-6 p-6">
        <div>
            <flux:heading size="xl">Import Participants</flux:heading>
            <flux:text class="mt-1 text-black/70">
                Upload the official participant list provided by authorized summit leadership.
            </flux:text>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-xl border border-black/5 bg-white">
                <div class="border-b border-black/5 px-6 py-4">
                    <flux:heading size="md">Upload Excel File</flux:heading>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.import.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <flux:field>
                                    <flux:label>Excel File (.xlsx)</flux:label>
                                    <input type="file" name="file" accept=".xlsx" class="w-full rounded-lg border border-black/10 p-2" required />
                                    @error('file')
                                        <flux:text class="text-red-600">{{ $message }}</flux:text>
                                    @enderror
                                </flux:field>
                            </div>

                            <div class="rounded-lg border border-blue-600/20 bg-blue-600/5 p-4">
                                <flux:text class="text-sm text-black/70">
                                    <strong>Requirements:</strong>
                                    <ul class="mt-2 list-disc space-y-1 pl-5">
                                        <li>File format: .xlsx only</li>
                                        <li>Maximum file size: 10MB</li>
                                        <li>Required columns: First Name, Last Name</li>
                                        <li>Optional columns: Contact, Age, Unit, Stake/District, Shirt Size</li>
                                    </ul>
                                </flux:text>
                            </div>

                            <div class="flex items-center gap-3">
                                <flux:button type="submit" variant="primary">
                                    <flux:icon name="arrow-up-tray" class="mr-2 h-4 w-4" />
                                    Upload and Preview
                                </flux:button>
                                <flux:button variant="ghost" :href="route('admin.import.template')" wire:navigate>
                                    <flux:icon name="arrow-down-tray" class="mr-2 h-4 w-4" />
                                    Download Template
                                </flux:button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="rounded-xl border border-black/5 bg-white">
                <div class="border-b border-black/5 px-6 py-4">
                    <flux:heading size="md">Import History</flux:heading>
                </div>
                <div class="p-6">
                    @if($imports->count() > 0)
                        <div class="space-y-3">
                            @foreach($imports as $import)
                                <div class="flex items-center justify-between rounded-lg border border-black/5 p-3">
                                    <div>
                                        <flux:text class="font-medium text-black">{{ $import->file_name }}</flux:text>
                                        <flux:text class="text-xs text-black/50">
                                            {{ $import->uploader->name ?? 'System' }} &middot; {{ $import->created_at->format('Y-m-d H:i') }}
                                        </flux:text>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($import->status === \App\Models\ParticipantImport::STATUS_COMPLETED)
                                            <flux:badge color="green" size="sm">Completed</flux:badge>
                                        @elseif($import->status === \App\Models\ParticipantImport::STATUS_COMPLETED_WITH_ERRORS)
                                            <flux:badge color="yellow" size="sm">With Errors</flux:badge>
                                        @elseif($import->status === \App\Models\ParticipantImport::STATUS_FAILED)
                                            <flux:badge color="red" size="sm">Failed</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">Processing</flux:badge>
                                        @endif
                                        <flux:button size="sm" variant="ghost" :href="route('admin.import.show', $import)" wire:navigate>View</flux:button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4">
                            {{ $imports->links() }}
                        </div>
                    @else
                        <flux:text class="text-black/70">No imports yet.</flux:text>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts::admin>
