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
        $this->call([
            ProvinceSeeder::class,
            CitySeeder::class,
            CategorySeeder::class,
        ]);

        User::create([
            'name' => 'Ricky Primayuda Putra',
            'email' => 'rickyprima30@gmail.com',
            'email_verified_at' => now(),
            'password' => 'rickyprima30@gmail.com',
            'role' => 1,
        ]);
        User::create([
            'name' => 'Seni Kita Admin',
            'email' => 'senikita@gmail.com',
            'email_verified_at' => now(),
            'password' => 'senikita@gmail.com',
            'role' => 1,
        ]);
        User::create([
            'name' => 'Mario Aprilnino',
            'email' => 'mario.aprilnino27@gmail.com',
            'email_verified_at' => now(),
            'password' => 'mario.aprilnino27@gmail.com',
            'role' => 0,
        ]);
    }
}
