<?php

use App\Models\User;

test('database works in admin subdirectory', function () {
    $user = User::factory()->create();
    $this->assertDatabaseHas('users', ['email' => $user->email]);
});
