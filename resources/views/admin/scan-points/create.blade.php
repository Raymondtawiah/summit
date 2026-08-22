<x-layouts::admin :title="__('Create Access Point')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('admin.scan-points') }}" wire:navigate>
                <flux:icon name="arrow-left" class="mr-2 h-4 w-4" />
                Back to Access Points
            </flux:button>
        </div>

        <div class="rounded-xl border border-black/5 bg-white p-6">
            <flux:heading size="lg" class="mb-6">Create Access Point</flux:heading>

            <form method="POST" action="{{ route('admin.scan-points.store') }}">
                @csrf
                <div class="grid grid-cols-1 gap-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <flux:label>Name</flux:label>
                            <flux:input type="text" name="name" value="{{ old('name') }}" required />
                            @error('name')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                        <div>
                            <flux:label>Code</flux:label>
                            <flux:input type="text" name="code" value="{{ old('code') }}" />
                            @error('code')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <flux:label>Type</flux:label>
                            <flux:select name="type">
                                <option value="transport">Transport</option>
                                <option value="accommodation">Accommodation</option>
                                <option value="entrance">Entrance</option>
                                <option value="meal">Meal</option>
                                <option value="activity">Activity</option>
                                <option value="session">Session</option>
                                <option value="other">Other</option>
                            </flux:select>
                            @error('type')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                        <div>
                            <flux:label>Attendance Rule</flux:label>
                            <flux:select name="duplicate_rule">
                                <option value="once_ever">Once Ever</option>
                                <option value="once_per_day">Once Per Day</option>
                                <option value="once_per_session">Once Per Session</option>
                                <option value="multiple_allowed">Multiple Allowed</option>
                            </flux:select>
                            @error('duplicate_rule')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <flux:label>Location</flux:label>
                        <flux:input type="text" name="location" value="{{ old('location') }}" />
                        @error('location')
                            <flux:text class="text-red-600">{{ $message }}</flux:text>
                        @enderror
                    </div>

                    <div>
                        <flux:label>Description</flux:label>
                        <flux:textarea name="description" rows="3">{{ old('description') }}</flux:textarea>
                        @error('description')
                            <flux:text class="text-red-600">{{ $message }}</flux:text>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <flux:label>Start Date</flux:label>
                            <flux:input type="date" name="start_date" value="{{ old('start_date') }}" />
                            @error('start_date')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                        <div>
                            <flux:label>End Date</flux:label>
                            <flux:input type="date" name="end_date" value="{{ old('end_date') }}" />
                            @error('end_date')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div>
                            <flux:label>Start Time</flux:label>
                            <flux:input type="time" name="start_time" value="{{ old('start_time') }}" />
                            @error('start_time')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                        <div>
                            <flux:label>End Time</flux:label>
                            <flux:input type="time" name="end_time" value="{{ old('end_time') }}" />
                            @error('end_time')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                        <div>
                            <flux:label>Capacity</flux:label>
                            <flux:input type="number" name="capacity" value="{{ old('capacity') }}" min="0" />
                            @error('capacity')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
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
                        <div class="flex items-center gap-2 pt-6">
                            <flux:checkbox name="requires_ticket" value="1" {{ old('requires_ticket', true) ? 'checked' : '' }} />
                            <flux:label>Requires valid ticket</flux:label>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex gap-2">
                    <flux:button variant="primary" type="submit">Create Access Point</flux:button>
                    <flux:button variant="ghost" href="{{ route('admin.scan-points') }}" wire:navigate>Cancel</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::admin>
