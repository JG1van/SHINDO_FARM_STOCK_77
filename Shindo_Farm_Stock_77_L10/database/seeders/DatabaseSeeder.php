<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin Shindo Farm',
            'email' => 'gian123ivan@gmail.com',
            'password' => 'admin-77', // otomatis di-hash oleh cast 'password' => 'hashed' di Model User
        ]);
    }
}