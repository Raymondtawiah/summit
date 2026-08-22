<x-layouts::admin :title="__('Import Preview')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Import Preview</flux:heading>
                <flux:text class="mt-1 text-black/70">
                    Review the data before confirming the import.
                </flux:text>
            </div>
            <div class="flex items-center gap-2">
                <flux:button variant="ghost" href="{{ route('admin.import') }}" wire:navigate>
                    Cancel
                </flux:button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-sm text-black/70">Total Rows</flux:text>
                <flux:heading size="xl" class="mt-1">{{ $preview['total_rows'] }}</flux:heading>
            </div>
            <div class="rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-sm text-black/70">Valid Rows</flux:text>
                <flux:heading size="xl" class="mt-1 text-green-600">{{ $preview['total_rows'] - count($preview['error_rows'] ?? []) }}</flux:heading>
            </div>
            <div class="rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-sm text-black/70">Invalid Rows</flux:text>
                <flux:heading size="xl" class="mt-1 text-red-600">{{ count($preview['error_rows'] ?? []) }}</flux:heading>
            </div>
            <div class="rounded-xl border border-black/5 bg-white p-4">
                <flux:text class="text-sm text-black/70">File</flux:text>
                <flux:text class="mt-1 text-sm text-black">{{ $fileName }}</flux:text>
            </div>
        </div>

        @if(!empty($preview['error_rows']))
            <div class="rounded-xl border border-red-600/20 bg-red-600/5 p-4">
                <flux:heading size="sm" class="text-red-600">Validation Errors</flux:heading>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">
                    @foreach($preview['error_rows'] as $error)
                        <li>{{ implode(', ', $error['errors']) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="border-b border-black/5 px-6 py-4">
                <flux:heading size="lg">Preview (First 100 rows)</flux:heading>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-black/5 bg-black/[0.02]">
                        <tr>
                            @foreach($preview['headers'] as $header)
                                <th class="px-4 py-2 font-medium">{{ $header }}</th>
                            @endforeach
                            <th class="px-4 py-2 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5">
                        @foreach($preview['rows'] as $row)
                            <tr>
                                @foreach($row as $cell)
                                    <td class="px-4 py-2">{{ $cell ?? '—' }}</td>
                                @endforeach
                                <td class="px-4 py-2">
                                    <flux:badge color="green" size="sm">Valid</flux:badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.import.confirm') }}">
            @csrf
            <input type="hidden" name="temp_path" value="{{ $tempPath }}" />
            <input type="hidden" name="file_name" value="{{ $fileName }}" />

            <div class="flex items-center justify-end gap-3">
                <flux:button type="submit" variant="primary">
                    <flux:icon name="check" class="mr-2 h-4 w-4" />
                    Confirm Import
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::admin>
