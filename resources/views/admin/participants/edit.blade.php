<x-layouts::admin :title="__('Edit Participant')">
    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" :href="route('admin.participants.show', $participant)" wire:navigate>
                <flux:icon name="arrow-left" class="mr-2 h-4 w-4" />
                Back to Participant
            </flux:button>
            <flux:heading size="lg">Edit Participant</flux:heading>
        </div>

        <div class="rounded-xl border border-black/5 bg-white">
            <div class="border-b border-black/5 px-6 py-4">
                <flux:text class="text-black/70">
                    Registration Number: <span class="font-mono font-medium text-black">{{ $participant->registration_number }}</span>
                </flux:text>
                <flux:text class="text-xs text-black/50">Registration number cannot be changed.</flux:text>
            </div>

            <form method="POST" action="{{ route('admin.participants.update', $participant) }}" class="p-6">
                @csrf
                @method('PUT')

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <flux:field>
                            <flux:label>First Name</flux:label>
                            <flux:input name="first_name" value="{{ old('first_name', $participant->first_name) }}" required />
                            @error('first_name')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </flux:field>
                    </div>

                    <div>
                        <flux:field>
                            <flux:label>Last Name</flux:label>
                            <flux:input name="last_name" value="{{ old('last_name', $participant->last_name) }}" required />
                            @error('last_name')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </flux:field>
                    </div>

                    <div>
                        <flux:field>
                            <flux:label>Contact</flux:label>
                            <flux:input name="contact" value="{{ old('contact', $participant->contact) }}" />
                            @error('contact')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </flux:field>
                    </div>

                    <div>
                        <flux:field>
                            <flux:label>Age</flux:label>
                            <flux:input type="number" name="age" value="{{ old('age', $participant->age) }}" min="0" max="150" />
                            @error('age')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </flux:field>
                    </div>

                    <div>
                        <flux:field>
                            <flux:label>Unit</flux:label>
                            <flux:input name="unit" value="{{ old('unit', $participant->unit) }}" />
                            @error('unit')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </flux:field>
                    </div>

                    <div>
                        <flux:field>
                            <flux:label>Stake/District</flux:label>
                            <flux:input name="stake_district" value="{{ old('stake_district', $participant->stake_district) }}" />
                            @error('stake_district')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </flux:field>
                    </div>

                    <div>
                        <flux:field>
                            <flux:label>Shirt Size</flux:label>
                            <flux:input name="shirt_size" value="{{ old('shirt_size', $participant->shirt_size) }}" />
                            @error('shirt_size')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </flux:field>
                    </div>

                    <div>
                        <flux:field>
                            <flux:label>Status</flux:label>
                            <flux:select name="status">
                                <option value="active" {{ old('status', $participant->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $participant->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </flux:select>
                            @error('status')
                                <flux:text class="text-red-600">{{ $message }}</flux:text>
                            @enderror
                        </flux:field>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <flux:button type="submit" variant="primary">Update Participant</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::admin>
