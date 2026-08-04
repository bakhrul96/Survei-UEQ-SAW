<?php

use App\Models\User;

it('creates an admin with a hashed password', function () {
    $this->artisan('app:create-admin', ['email' => 'peneliti@example.test'])
        ->expectsQuestion('Nama', 'Peneliti')
        ->expectsQuestion('Password', 'Rahasia-12345')
        ->assertSuccessful();

    $admin = User::query()->where('email', 'peneliti@example.test')->firstOrFail();

    expect($admin->name)->toBe('Peneliti')
        ->and($admin->password)->not->toBe('Rahasia-12345');
});
