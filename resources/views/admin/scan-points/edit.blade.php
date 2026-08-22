<x-layouts::admin :title="__('Edit Access Point')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" :href="route('admin.scan-points.show', $scanPoint)" wire:navigate>
                <flux:icon name="arrow-left" class="mr-2 h-4 w-4" />
                Back to Access Point
            </flux:button>
        </div>

        <div class="rounded-xl border border-black/5 bg-white p-6">
            <flux:heading size="lg" class="mb-6">Edit Access Point</flux:heading>

            <form method="POST" action="{{ route('admin.scan-points.update', $scanPoint) }}">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <flux:label>Name</flux:label>
                            <flux:input type="text" name="name" value="{{ old('name', $scanPoint->name) }}" required />
                            @error('name')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                        <div>
                            <flux:label>Code</flux:label>
                            <flux:input type="text" name="code" value="{{ old('code', $scanPoint->code) }}" />
                            @error('code')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <flux:label>Type</flux:label>
                            <flux:select name="type">
                                <option value="transport" {{ old('type', $scanPoint->type) === 'transport' ? 'selected' : '' }}>Transport</option>
                                <option value="accommodation" {{ old('type', $scanPoint->type) === 'accommodation' ? 'selected' : '' }}>Accommodation</option>
                                <option value="entrance" {{ old('type', $scanPoint->type) === 'entrance' ? 'selected' : '' }}>Entrance</option>
                                <option value="meal" {{ old('type', $scanPoint->type) === 'meal' ? 'selected' : '' }}>Meal</option>
                                <option value="activity" {{ old('type', $scanPoint->type) === 'activity' ? 'selected' : '' }}>Activity</option>
                                <option value="session" {{ old('type', $scanPoint->type) === 'session' ? 'selected' : '' }}>Session</option>
                                <option value="other" {{ old('type', $scanPoint->type) === 'other' ? 'selected' : '' }}>Other</option>
                            </flux:select>
                            @error('type')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                        <div>
                            <flux:label>Attendance Rule</flux:label>
                            <flux:select name="duplicate_rule">
                                <option value="once_ever" {{ old('duplicate_rule', $scanPoint->duplicate_rule) === 'once_ever' ? 'selected' : '' }}>Once Ever</option>
                                <option value="once_per_day" {{ old('duplicate_rule', $scanPoint->duplicate_rule) === 'once_per_day' ? 'selected' : '' }}>Once Per Day</option>
                                <option value="once_per_session" {{ old('duplicate_rule', $scanPoint->duplicate_rule) === 'once_per_session' ? 'selected' : '' }}>Once Per Session</option>
                                <option value="multiple_allowed" {{ old('duplicate_rule', $scanPoint->duplicate_rule) === 'multiple_allowed' ? 'selected' : '' }}>Multiple Allowed</option>
                            </flux:select>
                            @error('duplicate_rule')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <flux:label>Location</flux:label>
                        <flux:input type="text" name="location" value="{{ old('location', $scanPoint->location) }}" />
                        @error('location')
                            <flux:text class="text-red-600">{{ $message }}</flux:text>
                        @enderror
                    </div>

                    <div>
                        <flux:label>Description</flux:label>
                        <flux:textarea name="description" rows="3">{{ old('description', $scanPoint->description) }}</flux:textarea>
                        @error('description')
                            <flux:text class="text-red-600">{{ $message }}</flux:text>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <flux:label>Start Date</flux:label>
                            <flux:input type="date" name="start_date" value="{{ old('start_date', $scanPoint->start_date?->format('Y-m-d')) }}" />
                            @error('start_date')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                        <div>
                            <flux:label>End Date</flux:label>
                            <flux:input type="date" name="end_date" value="{{ old('end_date', $scanPoint->end_date?->format('Y-m-d')) }}" />
                            @error('end_date')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div>
                            <flux:label>Start Time</flux:label>
                            <flux:input type="time" name="start_time" value="{{ old('start_time', $scanPoint->start_time?->format('H:i')) }}" />
                            @error('start_time')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                        <div>
                            <flux:label>End Time</flux:label>
                            <flux:input type="time" name="end_time" value="{{ old('end_time', $scanPoint->end_time?->format('H:i')) }}" />
                            @error('end_time')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                        <div>
                            <flux:label>Capacity</flux:label>
                            <flux:input type="number" name="capacity" value="{{ old('capacity', $scanPoint->capacity) }}" min="0" />
                            @error('capacity')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <flux:label>Status</flux:label>
                            <flux:select name="status">
                                <option value="active" {{ old('status', $scanPoint->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $scanPoint->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </flux:select>
                            @error('status')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <flux:checkbox name="requires_ticket" value="1" {{ old('requires_ticket', $scanPoint->requires_ticket) ? 'checked' : '' }} />
                            <flux:label>Requires valid ticket</flux:label>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex gap-2">
                    <flux:button variant="primary" type="submit">Update Access Point</flux:button>
                    <flux:button variant="ghost" :href="route('admin.scan-points.show', $scanPoint)" wire:navigate>Cancel</flux:button>
                </div>
            </form>
        </div>

        <div class="rounded-xl border border-red-200 bg-red-50 p-6">
            <flux:heading size="md" class="mb-2 text-red-800">Danger Zone</flux:heading>
            <flux:text class="text-sm text-red-700 mb-4">These actions cannot be undone.</flux:text>
            <div class="flex flex-wrap gap-2">
                @if($scanPoint->status === 'active')
                    <form method="POST" action="{{ route('admin.scan-points.deactivate', $scanPoint) }}" onsubmit="return confirm('Are you sure you want to deactivate this access point?')">
                        @csrf
                        @method('POST')
                        <flux:button variant="danger" type="submit">Deactivate Access Point</flux:button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.scan-points.activate', $scanPoint) }}">
                        @csrf
                        @method('POST')
                        <flux:button variant="primary" type="submit">Activate Access Point</flux:button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-layouts::admin>
