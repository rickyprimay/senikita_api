<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'Ricky Primayuda Putra',
            'email' => 'rickyprima30@gmail.com',
            'password' => 'rickyprima30@gmail.com',
            'role' => 1,
        ]);
        User::create([
            'name' => 'Mario Aprilnino',
            'email' => 'mario.aprilnino27@gmail.com',
            'password' => 'mario.aprilnino27@gmail.com',
            'role' => 0,
        ]);
    }
}
