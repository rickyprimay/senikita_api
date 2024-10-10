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
            'email_verified_at' => now(),
            'birth_date' => '2004-05-22',
            'birth_location' => 'Purwodadi',
            'call_number' => '0895363185264',
            'gender' => 'male',
            'password' => 'rickyprima30@gmail.com',
            'role' => 0,
        ]);
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
        User::create([
            'name' => 'Mario Aprilnino',
            'email' => 'mario.aprilnino27@gmail.com',
            'email_verified_at' => now(),
            'call_number' => '0895363185264',
            'gender' => 'male',
            'birth_date' => '2004-05-22',
            'birth_location' => 'Purwodadi',
            'password' => 'mario.aprilnino27@gmail.com',
            'role' => 0,
            'isHaveStore' => 1,
        ]);

        User::create([
            'name' => 'Mario Aprilnino',
            'email' => 'mario.1@gmail.com',
            'email_verified_at' => now(),
            'call_number' => '0895363185264',
            'gender' => 'male',
            'birth_date' => '2004-05-22',
            'birth_location' => 'Purwodadi',
            'password' => 'mario.1@gmail.com',
            'role' => 0,
            'isHaveStore' => 1,
        ]);
        User::create([
            'name' => 'Mario Aprilnino',
            'email' => 'sanggar_semarang@gmail.com',
            'email_verified_at' => now(),
            'call_number' => '0895363185264',
            'gender' => 'male',
            'birth_date' => '2004-05-22',
            'birth_location' => 'Purwodadi',
            'password' => 'sanggar_semarang@gmail.com',
            'role' => 0,
            'isHaveStore' => 1,
        ]);
        User::create([
            'name' => 'Mario Aprilnino',
            'email' => 'sanggar_bali@gmail.com',
            'email_verified_at' => now(),
            'call_number' => '0895363185264',
            'gender' => 'male',
            'birth_date' => '2004-05-22',
            'birth_location' => 'Purwodadi',
            'password' => 'sanggar_bali@gmail.com',
            'role' => 0,
            'isHaveStore' => 1,
        ]);
        User::create([
            'name' => 'Mario Aprilnino',
            'email' => 'sanggarbari@gmail.com',
            'email_verified_at' => now(),
            'call_number' => '0895363185264',
            'gender' => 'male',
            'birth_date' => '2004-05-22',
            'birth_location' => 'Purwodadi',
            'password' => 'sanggarbari@gmail.com',
            'role' => 0,
            'isHaveStore' => 1,
        ]);

        $this->call([
            ProvinceSeeder::class,
            CitySeeder::class,
            CategorySeeder::class,
            ProductServiceSeeder::class,
        ]);
    }
}
