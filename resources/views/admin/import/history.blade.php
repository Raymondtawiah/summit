<x-layouts::admin :title="__('Import History')">
    <div class="flex flex-col gap-6 p-6">
        <div>
            <flux:heading size="xl">Import History</flux:heading>
            <flux:text class="mt-1 text-black/70">
                View past participant imports.
            </flux:text>
        </div>

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-black/5 bg-black/[0.02]">
                        <tr>
                            <th class="px-6 py-3 font-medium">File Name</th>
                            <th class="px-6 py-3 font-medium">Uploaded By</th>
                            <th class="px-6 py-3 font-medium">Total</th>
                            <th class="px-6 py-3 font-medium">Imported</th>
                            <th class="px-6 py-3 font-medium">Updated</th>
                            <th class="px-6 py-3 font-medium">Skipped</th>
                            <th class="px-6 py-3 font-medium">Duplicates</th>
                            <th class="px-6 py-3 font-medium">Errors</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 font-medium">Date</th>
                            <th class="px-6 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @forelse($imports as $import)
                            <tr>
                                <td class="px-6 py-4 font-medium text-black">{{ $import->file_name }}</td>
                                <td class="px-6 py-4 text-black/70">{{ $import->uploader->name ?? 'System' }}</td>
                                <td class="px-6 py-4 text-black/70">{{ $import->total_rows }}</td>
                                <td class="px-6 py-4 text-black/70">{{ $import->imported_count }}</td>
                                <td class="px-6 py-4 text-black/70">{{ $import->updated_count }}</td>
                                <td class="px-6 py-4 text-black/70">{{ $import->skipped_count }}</td>
                                <td class="px-6 py-4 text-black/70">{{ $import->duplicate_count }}</td>
                                <td class="px-6 py-4 text-black/70">{{ $import->error_count }}</td>
                                <td class="px-6 py-4">
                                    @if($import->status === \App\Models\ParticipantImport::STATUS_COMPLETED)
                                        <flux:badge color="green" size="sm">Completed</flux:badge>
                                    @elseif($import->status === \App\Models\ParticipantImport::STATUS_COMPLETED_WITH_ERRORS)
                                        <flux:badge color="yellow" size="sm">With Errors</flux:badge>
                                    @elseif($import->status === \App\Models\ParticipantImport::STATUS_FAILED)
                                        <flux:badge color="red" size="sm">Failed</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">Processing</flux:badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-black/70">{{ $import->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-6 py-4">
                                    <flux:button size="sm" variant="ghost" :href="route('admin.import.show', $import)" wire:navigate>View</flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-12 text-center text-black/70">
                                    No import history found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-black/5 px-6 py-4">
                {{ $imports->links() }}
            </div>
        </div>
    </div>
</x-layouts::admin>
