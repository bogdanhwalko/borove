<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ArticleSeeder::class,
            AnnouncementSeeder::class,
            RideSeeder::class,
            AlbumPhotoSeeder::class,
            ShopProductSeeder::class,
        ]);
    }
}
