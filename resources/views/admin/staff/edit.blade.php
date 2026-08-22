<x-layouts::admin :title="__('Edit Staff Account')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" :href="route('admin.staff.show', $staff)" wire:navigate>
                <flux:icon name="arrow-left" class="mr-2 h-4 w-4" />
                Back to Staff
            </flux:button>
        </div>

        <div class="rounded-xl border border-black/5 bg-white p-6">
            <flux:heading size="lg" class="mb-6">Edit Staff Account</flux:heading>

            <form method="POST" action="{{ route('admin.staff.update', $staff) }}">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <flux:label>Full Name</flux:label>
                        <flux:input type="text" name="name" value="{{ old('name', $staff->name) }}" required />
                        @error('name')
                            <flux:text class="text-red-600">{{ $message }}</flux:text>
                        @enderror
                    </div>
                    <div>
                        <flux:label>Email</flux:label>
                        <flux:input type="email" name="email" value="{{ old('email', $staff->email) }}" required />
                        @error('email')
                            <flux:text class="text-red-600">{{ $message }}</flux:text>
                        @enderror
                    </div>
                    <div>
                        <flux:label>Status</flux:label>
                        <flux:select name="status">
                            <option value="active" {{ old('status', $staff->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $staff->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </flux:select>
                        @error('status')
                            <flux:text class="text-red-600">{{ $message }}</flux:text>
                        @enderror
                    </div>
                    <div>
                        <flux:label>Scan Point</flux:label>
                        <flux:select name="scan_point_id">
                            <option value="">None</option>
                            @foreach($scanPoints as $scanPoint)
                                <option value="{{ $scanPoint->id }}" {{ old('scan_point_id', $staff->scan_point_id) == $scanPoint->id ? 'selected' : '' }}>{{ $scanPoint->name }} - {{ $scanPoint->location }}</option>
                            @endforeach
                        </flux:select>
                        @error('scan_point_id')
                            <flux:text class="text-red-600">{{ $message }}</flux:text>
                        @enderror
                    </div>
                </div>
                <div class="mt-6 flex gap-2">
                    <flux:button variant="primary" type="submit">Update Staff Account</flux:button>
                    <flux:button variant="ghost" :href="route('admin.staff.show', $staff)" wire:navigate>Cancel</flux:button>
                </div>
            </form>
        </div>

        <div class="rounded-xl border border-red-200 bg-red-50 p-6">
            <flux:heading size="md" class="mb-2 text-red-800">Danger Zone</flux:heading>
            <flux:text class="text-sm text-red-700 mb-4">These actions cannot be undone.</flux:text>
            <div class="flex flex-wrap gap-2">
                @if($staff->status === 'active')
                    <form method="POST" action="{{ route('admin.staff.deactivate', $staff) }}" onsubmit="return confirm('Are you sure you want to deactivate this staff member?')">
                        @csrf
                        @method('POST')
                        <flux:button variant="danger" type="submit">Deactivate Staff</flux:button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.staff.activate', $staff) }}">
                        @csrf
                        @method('POST')
                        <flux:button variant="primary" type="submit">Activate Staff</flux:button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-layouts::admin>
