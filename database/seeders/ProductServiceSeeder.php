<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void

    {
        $seederShop = [
            [
                'name' => 'Toko Seni Bali',
                'desc' => 'Toko seni yang menjual berbagai macam kerajinan khas Bali.',
                'city_id' => 1,
                'province_id' => 1,
                'user_id' => 3,
                'profile_picture' => 'https://smartbintaro.com/wp-content/uploads/2023/09/IG-Sanggar-Sanggita-Kencana-Budaya.jpg',
            ],
            [
                'name' => 'Toko Seni Jawa Barat',
                'desc' => 'Toko seni yang menjual berbagai macam kerajinan khas Jawa Barat.',
                'city_id' => 2,
                'province_id' => 2,
                'user_id' => 4,
                'profile_picture' => 'https://pandeyan.magetan.go.id/media/img/berita/berita_50476152a2dbf1f652.04601186.jpg',
            ],
            [
                'name' => 'Toko Seni Jawa Tengah',
                'desc' => 'Toko seni yang menjual berbagai macam kerajinan khas Jawa Tengah.',
                'city_id' => 3,
                'province_id' => 3,
                'user_id' => 5,
                'profile_picture' => 'https://homestaydijogja.net/wp-content/uploads/2024/01/Toko-Batik-Jogja-Berkualitas.jpg',
            ],
            [
                'name' => 'Sanggar Seni Bali',
                'desc' => 'Sanggar seni yang menawarkan berbagai macam kesenian khas Bali.',
                'city_id' => 3,
                'province_id' => 3,
                'user_id' => 6,
                'profile_picture' => 'https://yt3.googleusercontent.com/ReVt-Ti3nkchXIkTV0_a_q11j-Nn9D9FwbTtKmXFpsoeW9Rs1kT4nTR1ef1KyieatvY6hyqf2g=s900-c-k-c0x00ffffff-no-rj',
            ],
            [
                'name' => 'Sanggar Seni Semarang',
                'desc' => 'Sanggar seni yang menawarkan berbagai macam kesenian khas Semarang.',
                'city_id' => 3,
                'province_id' => 3,
                'user_id' => 6,
                'profile_picture' => 'https://assets.promediateknologi.id/crop/0x0:0x0/750x500/webp/photo/2022/04/24/192013399.jpg',
            ]
        ];

        foreach ($seederShop as $shop) {
            DB::table('shop')->insert($shop);
        }

        DB::table('shop')->insert([
            'name' => 'Toko Seni Bali',
            'desc' => 'Toko seni yang menjual berbagai macam kerajinan khas Bali.',
            'city_id' => 1,
            'province_id' => 1,
            'user_id' => 3,
        ]);

        DB::table('product')->insert([
            [
                'name' => 'Batik Tulis Pekalongan',
                'price' => 150000,
                'desc' => 'Batik tulis asli dari Pekalongan dengan motif klasik.',
                'stock' => 20,
                'status' => 1,
                'thumbnail' => 'https://www.static-src.com/wcsstore/Indraprastha/images/catalog/full//104/MTA-11275979/gemah_sumilir_batik_tulis_pekalongan_full00.jpg',
                'category_id' => 1,
                'shop_id' => 1,
                'sold' => 5,
            ],
            [
                'name' => 'Patung Kayu Bali',
                'price' => 300000,
                'desc' => 'Patung kayu ukiran tangan khas Bali, berbentuk dewa-dewa Hindu.',
                'stock' => 10,
                'status' => 1,
                'thumbnail' => 'https://images.tokopedia.net/img/cache/700/VqbcmM/2021/5/9/f070cc4f-a61d-43b4-9939-f01f95f93776.jpg',
                'category_id' => 2,
                'shop_id' => 1,
                'sold' => 2,
            ],
            [
                'name' => 'Lukisan Tradisional Wayang',
                'price' => 200000,
                'desc' => 'Lukisan tradisional menggambarkan tokoh-tokoh wayang terkenal.',
                'stock' => 5,
                'status' => 1,
                'thumbnail' => 'https://awsimages.detik.net.id/community/media/visual/2023/04/12/lukisan-wayang-kamasan-facebook-lukisan-wayang-kamasan.jpeg?w=1200',
                'category_id' => 3,
                'shop_id' => 1,
                'sold' => 3,
            ],
            [
                'name' => 'Topeng Malangan',
                'price' => 80000,
                'desc' => 'Topeng khas Malang yang sering digunakan dalam tari topeng.',
                'stock' => 25,
                'status' => 1,
                'thumbnail' => 'https://asset-2.tstatic.net/surabaya/foto/bank/images/topeng-malangan_20150614_192004.jpg',
                'category_id' => 2,
                'shop_id' => 1,
                'sold' => 10,
            ],
            [
                'name' => 'Batik Cap Solo',
                'price' => 120000,
                'desc' => 'Batik cap khas Solo dengan desain modern dan elegan.',
                'stock' => 15,
                'status' => 1,
                'thumbnail' => 'https://batiks128.com/wp-content/uploads/products_img/k142.jpg',
                'category_id' => 1,
                'shop_id' => 1,
                'sold' => 7,
            ],
            [
                'name' => 'Kerajinan Perak Yogyakarta',
                'price' => 250000,
                'desc' => 'Kerajinan perak khas Yogyakarta yang dibuat dengan tangan.',
                'stock' => 8,
                'status' => 1,
                'thumbnail' => 'https://statics.indozone.news/local/62e68ab7780f9.jpg',
                'category_id' => 2,
                'shop_id' => 1,
                'sold' => 4,
            ],
            [
                'name' => 'Anyaman Bambu Jawa Barat',
                'price' => 50000,
                'desc' => 'Kerajinan anyaman bambu dari Jawa Barat yang serbaguna.',
                'stock' => 50,
                'status' => 1,
                'thumbnail' => 'https://indonesiakaya.com/wp-content/uploads/2020/10/Anyaman_bambu_1200.jpg',
                'category_id' => 2,
                'shop_id' => 1,
                'sold' => 20,
            ],
            [
                'name' => 'Patung Garuda Bali',
                'price' => 350000,
                'desc' => 'Patung Garuda khas Bali dengan ukiran detail yang indah.',
                'stock' => 5,
                'status' => 1,
                'thumbnail' => 'https://asset.kompas.com/crops/fhyXeS2borIrSmZbPebufHwNVdQ=/12x8:993x662/750x500/data/photo/2018/09/27/1679906755.jpg',
                'category_id' => 2,
                'shop_id' => 1,
                'sold' => 1,
            ],

        ]);


        DB::table('service')->insert([
            [
                'name' => 'Tari Pendet Bali',
                'price' => 500000,
                'desc' => 'Pertunjukan tari Pendet Bali yang dibawakan oleh penari profesional.',
                'type' => 'tampil',
                'status' => 1,
                'thumbnail' => 'https://www.its.ac.id/news/wp-content/uploads/sites/2/2024/04/Tari-Tradisional-Nusantara-Yang-Terkenal-Dan-Mendunia.jpeg',
                'sold' => 10,
                'person_amount' => 5,
                'category_id' => 2,
                'shop_id' => 1,
            ],
            [
                'name' => 'Pementasan Wayang Kulit',
                'price' => 1200000,
                'desc' => 'Pementasan wayang kulit lengkap dengan dalang dan gamelan.',
                'type' => 'tampil',
                'status' => 1,
                'thumbnail' => 'https://cdn1-production-images-kly.akamaized.net/hCnuNYQShsZh0Jg5R56aQLqt1PU=/1200x900/smart/filters:quality(75):strip_icc():format(webp)/kly-media-production/medias/4297673/original/070391300_1674225772-shutterstock_701666278.jpg',
                'sold' => 3,
                'person_amount' => 15,
                'category_id' => 1,
                'shop_id' => 1
            ],
            [
                'name' => 'Pelatihan Tari Jaipong',
                'price' => 300000,
                'desc' => 'Kelas pelatihan tari Jaipong untuk pemula, diajarkan oleh guru tari profesional.',
                'type' => 'jam',
                'status' => 1,
                'thumbnail' => 'https://asset.kompas.com/crops/VpXXfXOFR6F9T70pJ08HZZUjPOk=/0x0:0x0/750x500/data/photo/buku/63072e6009aaa.jpg',
                'sold' => 8,
                'person_amount' => 20,
                'category_id' => 2,
                'shop_id' => 1,
            ],

            [
                'name' => 'Pementasan Tari Kecak',
                'price' => 800000,
                'desc' => 'Pementasan tari Kecak Bali dengan penari profesional.',
                'type' => 'tampil',
                'status' => 1,
                'thumbnail' => 'https://asset.kompas.com/crops/VpXXfXOFR6F9T70pJ08HZZUjPOk=/0x0:0x0/750x500/data/photo/buku/63072e6009aaa.jpg',
                'sold' => 4,
                'person_amount' => 15,
                'category_id' => 2,
                'shop_id' => 1,
            ],
            [
                'name' => 'Pementasan Angklung',
                'price' => 700000,
                'desc' => 'Pementasan angklung dari Jawa Barat dengan grup musisi profesional.',
                'type' => 'tampil',
                'status' => 1,
                'thumbnail' => 'https://akcdn.detik.net.id/visual/2016/04/08/da1b2048-f03e-4d5d-a062-5a5830337f05_169.jpg?w=650',
                'sold' => 5,
                'person_amount' => 10,
                'category_id' => 2,
                'shop_id' => 1,
            ],
            [
                'name' => 'Tari Saman',
                'price' => 550000,
                'desc' => 'Pertunjukan Tari Saman dari Aceh, disajikan oleh penari profesional.',
                'type' => 'tampil',
                'status' => 1,
                'thumbnail' => 'https://asset.kompas.com/crops/VpXXfXOFR6F9T70pJ08HZZUjPOk=/0x0:0x0/750x500/data/photo/buku/63072e6009aaa.jpg',
                'sold' => 2,
                'person_amount' => 12,
                'category_id' => 2,
                'shop_id' => 1,
            ],
        ]);
    }
}
