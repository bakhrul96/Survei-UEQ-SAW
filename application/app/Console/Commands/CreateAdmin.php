<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Throwable;

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

        retry(
            3,
            function () use ($email, $name, $password): void {
                DB::transaction(function () use ($email, $name, $password): void {
                    DB::table('admin_singleton_locks')->where('id', 1)->update(['id' => 1]);

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
            },
            fn (int $attempt): int => $attempt * 100,
            fn (Throwable $exception): bool => $this->isRetryableLockException($exception),
        );

        $this->info('Akun Peneliti/Admin siap digunakan.');

        return self::SUCCESS;
    }

    private function isRetryableLockException(Throwable $exception): bool
    {
        if (! $exception instanceof QueryException) {
            return false;
        }

        $message = mb_strtolower($exception->getMessage());

        return str_contains($message, 'database is locked')
            || str_contains($message, 'database table is locked')
            || str_contains($message, 'deadlock found')
            || str_contains($message, 'lock wait timeout');
    }
}
