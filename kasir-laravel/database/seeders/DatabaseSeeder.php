<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;
use App\Models\PcClient;
use App\Models\BillingPackage;
use App\Models\FoodMenu;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Members
        $members = [
            [
                'username' => 'sultan01',
                'name' => 'Sultan Gaming',
                'password' => Hash::make('password'),
                'balance' => 150000,
                'saved_seconds' => 7200, // 2 Jam
            ],
            [
                'username' => 'gamer02',
                'name' => 'Rizky Pro Player',
                'password' => Hash::make('password'),
                'balance' => 55000,
                'saved_seconds' => 2700, // 45 Menit
            ],
            [
                'username' => 'budi99',
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'balance' => 25000,
                'saved_seconds' => 900, // 15 Menit
            ],
        ];

        foreach ($members as $m) {
            Member::updateOrCreate(['username' => $m['username']], $m);
        }

        // 2. Seed 10 PC Clients (PC-01 to PC-10)
        for ($i = 1; $i <= 10; $i++) {
            $code = sprintf('PC-%02d', $i);
            PcClient::updateOrCreate(
                ['pc_code' => $code],
                [
                    'name' => "Terminal VIP #$i",
                    'status' => 'locked',
                    'is_locked' => true,
                    'remaining_seconds_cache' => 0,
                ]
            );
        }

        // 3. Seed Billing Packages
        $packages = [
            ['name' => 'Paket Reguler 1 Jam', 'duration_minutes' => 60, 'price' => 7000, 'description' => '1 Jam akses penuh PC Gaming VIP'],
            ['name' => 'Paket Hemat 2 Jam', 'duration_minutes' => 120, 'price' => 13000, 'description' => 'Lebih hemat untuk sesi 2 jam'],
            ['name' => 'Paket Gamer 3 Jam', 'duration_minutes' => 180, 'price' => 18000, 'description' => 'Paket favorit mabar 3 jam'],
            ['name' => 'Paket Push Rank 5 Jam', 'duration_minutes' => 300, 'price' => 28000, 'description' => 'Puas main 5 jam non-stop'],
            ['name' => 'Paket Sultan 10 Jam', 'duration_minutes' => 600, 'price' => 50000, 'description' => 'Waktu bisa disimpan di akun member'],
            ['name' => 'Paket Kalong Malam', 'duration_minutes' => 420, 'price' => 25000, 'description' => '7 Jam paket spesial begadang'],
        ];

        foreach ($packages as $pkg) {
            BillingPackage::updateOrCreate(['name' => $pkg['name']], $pkg);
        }

        // 4. Seed Food & Beverage Menus
        $menus = [
            ['name' => 'Indomie Goreng Spesial Telur', 'category' => 'makanan', 'price' => 12000, 'stock' => 50],
            ['name' => 'Indomie Kuah Soto + Telur Kornet', 'category' => 'makanan', 'price' => 15000, 'stock' => 40],
            ['name' => 'Nasi Goreng Ukikura Spesial', 'category' => 'makanan', 'price' => 20000, 'stock' => 35],
            ['name' => 'Nasi Ayam Geprek Keju Lumer', 'category' => 'makanan', 'price' => 22000, 'stock' => 30],
            ['name' => 'Rice Bowl Chicken Teriyaki', 'category' => 'makanan', 'price' => 23000, 'stock' => 25],
            ['name' => 'Kopi Susu Gula Aren Dingin', 'category' => 'minuman', 'price' => 14000, 'stock' => 60],
            ['name' => 'Es Teh Manis Jumbo', 'category' => 'minuman', 'price' => 6000, 'stock' => 100],
            ['name' => 'Matcha Latte Ice Creamy', 'category' => 'minuman', 'price' => 16000, 'stock' => 45],
            ['name' => 'Extra Joss Susu Dingin', 'category' => 'minuman', 'price' => 10000, 'stock' => 80],
            ['name' => 'French Fries Saus Keju', 'category' => 'snack', 'price' => 15000, 'stock' => 40],
            ['name' => 'Roti Bakar Coklat Keju', 'category' => 'snack', 'price' => 14000, 'stock' => 35],
            ['name' => 'Sosis & Nugget Platter', 'category' => 'snack', 'price' => 18000, 'stock' => 30],
        ];

        foreach ($menus as $menu) {
            FoodMenu::updateOrCreate(['name' => $menu['name']], $menu);
        }
    }
}
