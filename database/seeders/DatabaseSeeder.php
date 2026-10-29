<?php

namespace Database\Seeders;

use App\Models\CanteenProfile;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (['mains', 'breakfast', 'snacks', 'drinks'] as $category) {
            ProductCategory::query()->firstOrCreate(['name' => $category]);
        }

        if (DB::table('menu_products')->count() === 0) {
            DB::table('menu_products')->insert([
                ['name' => 'Pork Adobo', 'category' => 'mains', 'description' => 'Filipino-style pork braised in savory sauce', 'price' => 40, 'image_path' => 'assets/Dishes/Pork Adobo.jpeg', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Menudo', 'category' => 'mains', 'description' => 'Classic Filipino pork and tomato stew', 'price' => 40, 'image_path' => 'assets/Dishes/Menudo.jpeg', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Pork Sinigang', 'category' => 'mains', 'description' => 'Sour, comforting soup with gabi', 'price' => 40, 'image_path' => 'assets/Dishes/Pork Sinigang.jpeg', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Fried Chicken', 'category' => 'mains', 'description' => 'Crispy, golden and freshly cooked', 'price' => 40, 'image_path' => 'assets/Dishes/Fried Chicken.jpeg', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Egado', 'category' => 'mains', 'description' => 'Savory Ilocano pork stew', 'price' => 40, 'image_path' => 'assets/Dishes/Egado.jpeg', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Sweet and Sour', 'category' => 'mains', 'description' => 'Tangy sweet and sour favorite', 'price' => 40, 'image_path' => 'assets/Dishes/Sweet and Sour.jpeg', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Chicken Adobo', 'category' => 'mains', 'description' => 'Tender chicken simmered in savory adobo sauce', 'price' => 40, 'image_path' => 'assets/Dishes/Chicken Adobo.jpeg', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Longganisa, Hotdog and Egg', 'category' => 'breakfast', 'description' => 'Breakfast favorites served together', 'price' => 40, 'image_path' => 'assets/Breakfast/eac20c86-2f3f-44f6-9459-b30a5f51ee4f.jpeg', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Luncheon Meat and Egg', 'category' => 'breakfast', 'description' => 'Seared luncheon meat with egg', 'price' => 40, 'image_path' => 'assets/Breakfast/95e538ce-91f3-4275-bfc2-e197714c097a.jpeg', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Siomai', 'category' => 'breakfast', 'description' => 'Steamed savory siomai', 'price' => 40, 'image_path' => 'assets/Breakfast/eac20c86-2f3f-44f6-9459-b30a5f51ee4f.jpeg', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Coke', 'category' => 'drinks', 'description' => 'Chilled Coca-Cola', 'price' => 40, 'image_path' => 'assets/Drink/Coke.jpeg', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Mountain Dew', 'category' => 'drinks', 'description' => 'Chilled Mountain Dew', 'price' => 40, 'image_path' => 'assets/Drink/Mountain Dew.jpeg', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        CanteenProfile::query()->firstOrCreate(['id' => 1], [
            'canteen_name' => 'Romy and Allit Food Haven',
            'operating_hours' => 'Monday - Saturday, 6:00 AM - 5:00 PM',
            'contact_info' => '',
            'pickup_location' => 'Main pickup counter',
        ]);

        $username = env('ADMIN_USERNAME');
        $password = env('ADMIN_PASSWORD');
        if ($username && $password) {
            User::query()->firstOrCreate(
                ['username' => $username],
                [
                    'full_name' => env('ADMIN_NAME', 'Canteen Administrator'),
                    'email' => env('ADMIN_EMAIL') ?: null,
                    'password_hash' => $password,
                    'role' => 'admin',
                    'email_verified' => true,
                ],
            );
        }
    }
}
