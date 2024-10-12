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
        User::create([
            'name' => 'Seni Kita Admin',
            'email' => 'senikita@gmail.com',
            'call_number' => '0895363185264',
            'gender' => 'male',
            'email_verified_at' => now(),
            'birth_date' => '2004-05-22',
            'birth_location' => 'Purwodadi',
            'password' => 'senikita@gmail.com',
            'role' => 1,
        ]);

        $this->call([
            ProvinceSeeder::class,
            CitySeeder::class,
            CategorySeeder::class,
            // ProductServiceSeeder::class,
        ]);
    }
}
