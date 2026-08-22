<x-layouts::admin :title="__('Coming Soon')">
    <div class="flex flex-col items-center justify-center gap-4 p-6">
        <flux:heading size="lg">Coming Soon</flux:heading>
        <flux:text class="text-center text-zinc-600 dark:text-zinc-400">
            This module will be implemented in a future phase.
        </flux:text>
        <flux:button variant="primary" href="{{ route('admin.dashboard') }}" wire:navigate>
            Back to Dashboard
        </flux:button>
    </div>
</x-layouts::admin>
