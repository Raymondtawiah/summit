<x-layouts::admin :title="__('Create Staff Account')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('admin.staff') }}" wire:navigate>
                <flux:icon name="arrow-left" class="mr-2 h-4 w-4" />
                Back to Staff
            </flux:button>
        </div>

        <div class="rounded-xl border border-black/5 bg-white p-6">
            <flux:heading size="lg" class="mb-6">Create Staff Account</flux:heading>

            <form method="POST" action="{{ route('admin.staff.store') }}">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <flux:label>Full Name</flux:label>
                        <flux:input type="text" name="name" value="{{ old('name') }}" required />
                        @error('name')
                            <flux:text class="text-red-600">{{ $message }}</flux:text>
                        @enderror
                    </div>
                    <div>
                        <flux:label>Email</flux:label>
                        <flux:input type="email" name="email" value="{{ old('email') }}" required />
                        @error('email')
                            <flux:text class="text-red-600">{{ $message }}</flux:text>
                        @enderror
                    </div>
                    <div>
                        <flux:label>Password</flux:label>
                        <flux:input type="password" name="password" required />
                        @error('password')
                            <flux:text class="text-red-600">{{ $message }}</flux:text>
                        @enderror
                    </div>
                    <div>
                        <flux:label>Confirm Password</flux:label>
                        <flux:input type="password" name="password_confirmation" required />
                    </div>
                    <div>
                        <flux:label>Status</flux:label>
                        <flux:select name="status">
                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                                <option value="{{ $scanPoint->id }}" {{ old('scan_point_id') == $scanPoint->id ? 'selected' : '' }}>{{ $scanPoint->name }} - {{ $scanPoint->location }}</option>
                            @endforeach
                        </flux:select>
                        @error('scan_point_id')
                            <flux:text class="text-red-600">{{ $message }}</flux:text>
                        @enderror
                    </div>
                </div>
                <div class="mt-6 flex gap-2">
                    <flux:button variant="primary" type="submit">Create Staff Account</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.staff') }}" wire:navigate>Cancel</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::admin>
