<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    protected $signature = 'make:super-admin {--name= : Super admin name} {--email= : Super admin email} {--password= : Password}';
    protected $description = 'Create a new super admin user';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Super Admin Name', 'Super Admin');
        $email = $this->option('email') ?: $this->ask('Super Admin Email');
        $password = $this->option('password') ?: $this->secret('Password');

        if (!$email || !$password) {
            $this->error('Email and password are required.');
            return self::FAILURE;
        }

        if (DB::table('super_admins')->where('email', $email)->exists()) {
            $this->error("Super admin with email {$email} already exists.");
            return self::FAILURE;
        }

        DB::table('super_admins')->insert([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("Super admin created successfully: {$email}");
        return self::SUCCESS;
    }
}
