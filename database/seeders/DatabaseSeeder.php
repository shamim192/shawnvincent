<?php

namespace Database\Seeders;

use App\Models\Size;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(SettingSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(NotificationSeeder::class);
        $this->call(PageSeeder::class);
        $this->call(TransactionSeeder::class);
        $this->call(GenreSeeder::class);
        $this->call(SizeSeeder::class);
        // $this->call(MusicSeeder::class);
    }
}
