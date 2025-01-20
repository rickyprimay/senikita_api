<?php

namespace Database\Seeders;

use App\Models\ArtProvince;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ArtProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $name = [
            [
                'name' => 'Jawa Tengah',
                'longitude' => 110.418,
                'latitude' => -7.150975
            ],
            [
                'name' => 'Jawa Timur',
                'longitude' => 112.732,
                'latitude' => -7.549167
            ],
            [
                'name' => 'Jawa Barat',
                'longitude' => 107.668,
                'latitude' => -6.914744
            ],
            [
                'name' => 'Bali',
                'longitude' => 115.188919,
                'latitude' => -8.409518
            ],
            [
                'name' => 'DKI Jakarta',
                'longitude' => 106.845599,
                'latitude' => -6.208763,
            ],
            [
                'name' => 'DI Yogyakarta',
                'longitude' => 110.3666,
                'latitude' => -7.7956
            ]
        ];

        foreach ($name as $key => $value) {
            ArtProvince::create($value);
        }
    }
}
