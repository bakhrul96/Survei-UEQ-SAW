<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {email}';

    protected $description = 'Create or replace the single researcher admin account';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $name = trim((string) $this->ask('Nama'));
        $password = (string) $this->secret('Password');

        if ($name === '' || mb_strlen($password) < 12) {
            $this->error('Nama wajib dan password minimal 12 karakter.');

            return self::FAILURE;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password), 'email_verified_at' => now()],
        );

        $this->info('Akun Peneliti/Admin siap digunakan.');

        return self::SUCCESS;
    }
}
