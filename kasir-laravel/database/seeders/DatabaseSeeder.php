<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;
use App\Models\PcClient;
use App\Models\BillingPackage;
use App\Models\FoodMenu;
use App\Models\GameVaultAccount;
use App\Models\DisklessServerConfig;
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
                'balance' => 250000,
                'saved_seconds' => 7200, // 2 Jam
            ],
            [
                'username' => 'gamer02',
                'name' => 'Rizky Pro Player',
                'password' => Hash::make('password'),
                'balance' => 95000,
                'saved_seconds' => 3600, // 1 Jam
            ],
            [
                'username' => 'budi99',
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'balance' => 45000,
                'saved_seconds' => 1800, // 30 Menit
            ],
        ];

        foreach ($members as $m) {
            Member::updateOrCreate(['username' => $m['username']], $m);
        }

        // 2. Seed 15 PC Clients (Diskless Network 2.5GbE)
        // - PC-01 to PC-07 (7 PC): Kelas Reguler
        // - PC-08 to PC-12 (5 PC): Kelas VIP
        // - PC-13 to PC-15 (3 PC): Kelas VIP++
        for ($i = 1; $i <= 15; $i++) {
            $code = sprintf('PC-%02d', $i);
            if ($i <= 7) {
                $type = 'reguler';
                $rate = 10000;
                $name = "Terminal Reguler #$i";
                $specs = "AMD RX 6600 / RTX 3050 / RAM 16GB / Monitor 180Hz (F2P Esports)";
            } elseif ($i <= 12) {
                $type = 'vip';
                $rate = 25000;
                $name = "Terminal VIP #$i";
                $specs = "Ryzen 5 7500F / RTX 4060 8GB / RAM 32GB / Monitor 180Hz IPS (PC Game Pass + Open World)";
            } else {
                $type = 'vip++';
                $rate = 35000;
                $name = "Terminal VIP++ Sultan #$i";
                $specs = "Core i7-14700KF / RTX 5060 GDDR7 / RAM 32GB / Monitor 240Hz 2K QHD (AAA Ray Tracing + OBS)";
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

        // 3. Seed Billing Packages (Sesuai Tarif: Reguler Rp10rb, VIP Rp25rb, VIP++ Rp35rb)
        $packages = [
            // Reguler Tier
            ['name' => 'Paket Reguler 1 Jam', 'duration_minutes' => 60, 'pc_type' => 'reguler', 'price' => 10000, 'description' => '1 Jam akses PC Reguler (F2P Esports: Valorant, CS2, Dota 2, Roblox)'],
            ['name' => 'Paket Reguler 3 Jam', 'duration_minutes' => 180, 'pc_type' => 'reguler', 'price' => 25000, 'description' => 'Hemat 3 jam bermain di PC Reguler (Hemat Rp 5.000)'],
            ['name' => 'Paket Reguler 5 Jam', 'duration_minutes' => 300, 'pc_type' => 'reguler', 'price' => 40000, 'description' => 'Paket santai 5 jam PC Reguler (Hemat Rp 10.000)'],

            // VIP Tier (Rp 25.000/jam)
            ['name' => 'Paket VIP 1 Jam', 'duration_minutes' => 60, 'pc_type' => 'vip', 'price' => 25000, 'description' => '1 Jam akses PC VIP (Ryzen 5 + RTX 4060 + PC Game Pass & Open World)'],
            ['name' => 'Paket VIP 3 Jam', 'duration_minutes' => 180, 'pc_type' => 'vip', 'price' => 65000, 'description' => '3 Jam turnamen & mabar performa tinggi PC VIP (Hemat Rp 10.000)'],
            ['name' => 'Paket VIP 5 Jam', 'duration_minutes' => 300, 'pc_type' => 'vip', 'price' => 100000, 'description' => '5 Jam VIP gaming non-stop (Hemat Rp 25.000)'],

            // VIP++ Tier (Rp 35.000/jam)
            ['name' => 'Paket VIP++ 1 Jam', 'duration_minutes' => 60, 'pc_type' => 'vip++', 'price' => 35000, 'description' => '1 Jam Sultan VIP++ (RTX 5060 GDDR7 + 240Hz 2K + Pinjam Akun Game AAA)'],
            ['name' => 'Paket VIP++ 3 Jam', 'duration_minutes' => 180, 'pc_type' => 'vip++', 'price' => 90000, 'description' => '3 Jam AAA Ray Tracing Ultra + Live Streaming OBS (Hemat Rp 15.000)'],
            ['name' => 'Paket VIP++ 5 Jam', 'duration_minutes' => 300, 'pc_type' => 'vip++', 'price' => 140000, 'description' => '5 Jam VIP++ non-stop setup terkencang (Hemat Rp 35.000)'],

            // All Tiers / Universal
            ['name' => 'Paket Kalong Malam (All PC)', 'duration_minutes' => 420, 'pc_type' => 'all', 'price' => 50000, 'description' => '7 Jam paket begadang tengah malam berlaku di seluruh terminal'],
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

        // 5. Seed Game Vault (Akun Sharing Steam AAA & Game Pass untuk VIP & VIP++)
        $vaultAccounts = [
            [
                'game_title' => 'Black Myth: Wukong',
                'platform' => 'steam',
                'account_username' => 'ukikura_wukong_01',
                'account_token' => 'VAULT_TOKEN_WUKONG_99182',
                'status' => 'available',
            ],
            [
                'game_title' => 'Cyberpunk 2077: Phantom Liberty',
                'platform' => 'steam',
                'account_username' => 'ukikura_cyber_01',
                'account_token' => 'VAULT_TOKEN_CYBER_2077X',
                'status' => 'available',
            ],
            [
                'game_title' => 'Tekken 8 Ultimate',
                'platform' => 'steam',
                'account_username' => 'ukikura_tekken_01',
                'account_token' => 'VAULT_TOKEN_TEKKEN_882',
                'status' => 'available',
            ],
            [
                'game_title' => 'Forza Horizon 5 Premium',
                'platform' => 'steam',
                'account_username' => 'ukikura_forza_01',
                'account_token' => 'VAULT_TOKEN_FORZA_FH5',
                'status' => 'available',
            ],
            [
                'game_title' => 'Red Dead Redemption 2',
                'platform' => 'steam',
                'account_username' => 'ukikura_rdr2_01',
                'account_token' => 'VAULT_TOKEN_RDR2_WILD',
                'status' => 'available',
            ],
            [
                'game_title' => 'Monster Hunter Wilds',
                'platform' => 'steam',
                'account_username' => 'ukikura_mhw_01',
                'account_token' => 'VAULT_TOKEN_MHW_ALPHA',
                'status' => 'available',
            ],
            [
                'game_title' => 'Grand Theft Auto V Legacy',
                'platform' => 'steam',
                'account_username' => 'ukikura_gtav_01',
                'account_token' => 'VAULT_TOKEN_GTAV_5521',
                'status' => 'available',
            ],
            [
                'game_title' => 'Microsoft PC Game Pass Ultimate',
                'platform' => 'gamepass',
                'account_username' => 'ukikura_gamepass_vip',
                'account_token' => 'VAULT_TOKEN_XBOX_PASS_VIP',
                'status' => 'available',
            ],
        ];

        foreach ($vaultAccounts as $acc) {
            GameVaultAccount::updateOrCreate(
                ['game_title' => $acc['game_title'], 'account_username' => $acc['account_username']],
                $acc
            );
        }

        // 6. Seed Server Diskless Configuration
        $disklessConfigs = [
            ['key' => 'master_drive', 'value' => '4TB NVMe PCIe Gen4 Enterprise (Drive D: Games)'],
            ['key' => 'network_speed', 'value' => '2.5GbE Full-Duplex Switch 16-Port'],
            ['key' => 'superuser_mode', 'value' => 'STANDBY'], // STANDBY, UPDATING, ACTIVE
            ['key' => 'scan_paths', 'value' => \App\Services\ServerGameScanner::getDefaultScanPaths()],
            ['key' => 'total_games', 'value' => '0 Game Terpasang'],
            ['key' => 'last_update_log', 'value' => 'Menunggu hasil scan diskless server...'],
        ];

        foreach ($disklessConfigs as $cfg) {
            DisklessServerConfig::updateOrCreate(['key' => $cfg['key']], $cfg);
        }

        // 7. Scan Game Asli yang Terinstal di Server
        \App\Services\ServerGameScanner::scan();
    }
}
