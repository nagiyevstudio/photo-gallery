<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetAdminPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:reset-password {email?} {password?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset or create the admin user password';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?: $this->ask('Enter admin email');
        $password = $this->argument('password') ?: $this->secret('Enter new admin password');

        if (empty($email) || empty($password)) {
            $this->error('Email and password cannot be empty!');
            return Command::FAILURE;
        }

        $admin = Admin::where('email', $email)->first();

        if ($admin) {
            $admin->password = Hash::make($password);
            $admin->save();
            $this->info("Password for admin '$email' has been reset successfully!");
        } else {
            if ($this->confirm("Admin '$email' not found. Do you want to create a new admin with this email?", true)) {
                Admin::create([
                    'email' => $email,
                    'password' => Hash::make($password),
                ]);
                $this->info("New admin '$email' created successfully!");
            } else {
                $this->error('Operation cancelled.');
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
