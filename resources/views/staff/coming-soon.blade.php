<x-layouts::staff :title="__('Coming Soon')">
    <div class="flex flex-col items-center justify-center gap-4 p-6">
        <flux:heading size="lg">Coming Soon</flux:heading>
        <flux:text class="text-center text-zinc-600 dark:text-zinc-400">
            This feature will be available in a future phase.
        </flux:text>
        <flux:button variant="primary" href="{{ route('staff.scanner') }}" wire:navigate>
            Back to Scanner
        </flux:button>
    </div>
</x-layouts::staff>
