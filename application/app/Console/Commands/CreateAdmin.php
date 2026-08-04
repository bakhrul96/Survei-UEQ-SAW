<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {email}';

    protected $description = 'Create or replace the single researcher admin account';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $name = trim((string) $this->ask('Nama'));
        $password = (string) $this->secret('Password');

        if (Validator::make(['email' => $email], ['email' => ['required', 'email']])->fails()) {
            $this->error('Email wajib dan harus berformat valid.');

            return self::FAILURE;
        }

        if ($name === '' || mb_strlen($password) < 12) {
            $this->error('Nama wajib dan password minimal 12 karakter.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($email, $name, $password): void {
            $admin = User::query()->lockForUpdate()->orderBy('id')->first();

            if ($admin === null) {
                User::query()->create([
                    'email' => $email,
                    'name' => $name,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ]);

                return;
            }

            $admin->update([
                'email' => $email,
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);

            User::query()->where('id', '!=', $admin->id)->delete();
        });

        $this->info('Akun Peneliti/Admin siap digunakan.');

        return self::SUCCESS;
    }
}
