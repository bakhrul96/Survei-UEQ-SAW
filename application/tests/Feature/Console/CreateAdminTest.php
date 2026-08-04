<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

it('creates an admin with a hashed password', function () {
    $this->artisan('app:create-admin', ['email' => '  PENELITI@EXAMPLE.TEST  '])
        ->expectsQuestion('Nama', 'Peneliti')
        ->expectsQuestion('Password', 'Rahasia-12345')
        ->assertSuccessful();

    $admin = User::query()->where('email', 'peneliti@example.test')->firstOrFail();

    expect($admin->name)->toBe('Peneliti')
        ->and(Hash::check('Rahasia-12345', $admin->password))->toBeTrue();
});

it('replaces the existing admin when a different email is supplied', function () {
    User::factory()->create([
        'email' => 'lama@example.test',
        'name' => 'Admin Lama',
        'password' => Hash::make('Rahasia-Lama'),
    ]);

    $this->artisan('app:create-admin', ['email' => 'baru@example.test'])
        ->expectsQuestion('Nama', 'Peneliti Baru')
        ->expectsQuestion('Password', 'Rahasia-Baru-123')
        ->assertSuccessful();

    expect(User::query()->count())->toBe(1);

    $admin = User::query()->sole();

    expect($admin->email)->toBe('baru@example.test')
        ->and($admin->name)->toBe('Peneliti Baru')
        ->and(Hash::check('Rahasia-Baru-123', $admin->password))->toBeTrue();
});

it('does not mutate users for an invalid email address', function () {
    User::factory()->create(['email' => 'tetap@example.test', 'name' => 'Tetap']);

    $this->artisan('app:create-admin', ['email' => 'bukan-email'])
        ->expectsQuestion('Nama', 'Tidak Dipakai')
        ->expectsQuestion('Password', 'Rahasia-Baru-123')
        ->assertFailed();

    expect(User::query()->count())->toBe(1)
        ->and(User::query()->sole()->email)->toBe('tetap@example.test');
});

it('does not mutate users when the email address is empty', function () {
    $this->artisan('app:create-admin', ['email' => '   '])
        ->expectsQuestion('Nama', 'Tidak Dipakai')
        ->expectsQuestion('Password', 'Rahasia-Baru-123')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('does not create an admin without a valid name and password', function () {
    $this->artisan('app:create-admin', ['email' => 'peneliti@example.test'])
        ->expectsQuestion('Nama', '')
        ->expectsQuestion('Password', 'singkat')
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

it('provides the seeded sentinel row that serializes admin creation', function () {
    if (! Schema::hasTable('admin_singleton_locks')) {
        expect(false)->toBeTrue();

        return;
    }

    expect(DB::table('admin_singleton_locks')->where('id', 1)->exists())->toBeTrue();
});
