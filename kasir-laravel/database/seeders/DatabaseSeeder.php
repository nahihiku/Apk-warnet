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

        // 2. Seed PC Clients (PC-01 to PC-10) with 3 Tiers: reguler, vip, vip++
        for ($i = 1; $i <= 10; $i++) {
            $code = sprintf('PC-%02d', $i);
            if ($i <= 6) {
                $type = 'reguler';
                $rate = 5000;
                $name = "Terminal Reguler #$i";
                $specs = "Core i5-12400F / RTX 3060 12GB / RAM 16GB / Monitor 165Hz";
            } elseif ($i <= 9) {
                $type = 'vip';
                $rate = 8000;
                $name = "Terminal VIP #$i";
                $specs = "Core i7-13700F / RTX 4070 12GB / RAM 32GB / Monitor 240Hz Curved";
            } else {
                $type = 'vip++';
                $rate = 12000;
                $name = "Terminal VIP++ Sultan #$i";
                $specs = "Core i9-14900K / RTX 4090 24GB / RAM 64GB / Monitor 360Hz ROG + Kursi Gaming Titan";
            }

            PcClient::updateOrCreate(
                ['pc_code' => $code],
                [
                    'name' => $name,
                    'pc_type' => $type,
                    'hourly_rate' => $rate,
                    'specs' => $specs,
                    'status' => 'locked',
                    'is_locked' => true,
                    'remaining_seconds_cache' => 0,
                ]
            );
        }

        // 3. Seed Billing Packages (Tiered: reguler, vip, vip++, all)
        $packages = [
            // Reguler Tier
            ['name' => 'Paket Reguler 1 Jam', 'duration_minutes' => 60, 'pc_type' => 'reguler', 'price' => 5000, 'description' => '1 Jam akses PC Reguler (i5 + RTX 3060)'],
            ['name' => 'Paket Reguler 3 Jam', 'duration_minutes' => 180, 'pc_type' => 'reguler', 'price' => 13000, 'description' => 'Hemat 3 jam bermain di PC Reguler'],
            ['name' => 'Paket Reguler 5 Jam', 'duration_minutes' => 300, 'pc_type' => 'reguler', 'price' => 20000, 'description' => 'Paket santai 5 jam PC Reguler'],

            // VIP Tier
            ['name' => 'Paket VIP 1 Jam', 'duration_minutes' => 60, 'pc_type' => 'vip', 'price' => 8000, 'description' => '1 Jam akses PC VIP (i7 + RTX 4070 + 240Hz)'],
            ['name' => 'Paket VIP 3 Jam', 'duration_minutes' => 180, 'pc_type' => 'vip', 'price' => 22000, 'description' => 'Paket favorit pro gamer 3 jam PC VIP'],
            ['name' => 'Paket VIP 5 Jam', 'duration_minutes' => 300, 'pc_type' => 'vip', 'price' => 35000, 'description' => '5 Jam turnamen & mabar performa tinggi'],

            // VIP++ Tier
            ['name' => 'Paket VIP++ 1 Jam', 'duration_minutes' => 60, 'pc_type' => 'vip++', 'price' => 12000, 'description' => '1 Jam Sultan PC VIP++ (i9 + RTX 4090 + 360Hz)'],
            ['name' => 'Paket VIP++ 3 Jam', 'duration_minutes' => 180, 'pc_type' => 'vip++', 'price' => 32000, 'description' => '3 Jam gaming ultra-high end setup sultan'],
            ['name' => 'Paket VIP++ 5 Jam', 'duration_minutes' => 300, 'pc_type' => 'vip++', 'price' => 50000, 'description' => '5 Jam VIP++ non-stop setup terkencang'],

            // All Tiers / Universal
            ['name' => 'Paket Kalong Malam (All PC)', 'duration_minutes' => 420, 'pc_type' => 'all', 'price' => 25000, 'description' => '7 Jam paket begadang berlaku di semua terminal'],
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
