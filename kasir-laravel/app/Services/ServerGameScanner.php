<?php

namespace App\Services;

use App\Models\DisklessServerConfig;
use App\Models\GameVaultAccount;
use App\Models\ServerGame;
use Illuminate\Support\Facades\Cache;

class ServerGameScanner
{
    public static function getDefaultScanPaths(): string
    {
        return implode(';', [
            'D:\\Games',
            'C:\\Games',
            'D:\\SteamLibrary',
            'C:\\Program Files (x86)\\Steam',
            'D:\\Delta Force',
            'D:\\Chunithm X-VERSE',
        ]);
    }

    /**
     * Scan seluruh game yang benar-benar terpasang di server/PC saat ini.
     */
    public static function scan(bool $pruneMissing = true): array
    {
        $lock = Cache::lock('server_game_scan_lock', 20);
        if (!$lock->get()) {
            $totalVisible = ServerGame::where('is_visible', true)->count();
            return ['total' => $totalVisible, 'added' => 0, 'updated' => 0, 'removed' => 0, 'total_gb' => '0'];
        }

        try {
            $rawPaths = DisklessServerConfig::where('key', 'scan_paths')->value('value');
            if (!$rawPaths) {
                $rawPaths = self::getDefaultScanPaths();
                DisklessServerConfig::updateOrCreate(['key' => 'scan_paths'], ['value' => $rawPaths]);
            }

            $scanPaths = array_filter(array_map('trim', explode(';', $rawPaths)));
            $discoveredExternalIds = [];
            $added = 0;
            $updated = 0;

            // 1. Scan Steam Libraries (libraryfolders.vdf + custom Steam folders)
            $steamGames = self::scanSteamLibraries($scanPaths);
            foreach ($steamGames as $g) {
                $discoveredExternalIds[] = $g['external_id'];
                $res = self::upsertGame($g);
                if ($res === 'created') $added++;
                elseif ($res === 'updated') $updated++;
            }

            // 2. Scan Standalone & Diskless Game Directories + Known Launchers
            $localGames = self::scanLocalFoldersAndLaunchers($scanPaths);
            foreach ($localGames as $g) {
                $discoveredExternalIds[] = $g['external_id'];
                $res = self::upsertGame($g);
                if ($res === 'created') $added++;
                elseif ($res === 'updated') $updated++;
            }

            // 3. Hapus entri hasil auto-scan yang sudah di-uninstall atau duplikat
            $removed = 0;
            if ($pruneMissing && count($discoveredExternalIds) > 0) {
                $removed = ServerGame::whereIn('source_type', ['steam', 'exe', 'shortcut'])
                    ->whereNotIn('external_id', $discoveredExternalIds)
                    ->delete();
            }

            // Bersihkan duplikat jika ada race condition sebelumnya
            $seenIds = [];
            foreach (ServerGame::orderBy('id')->get() as $row) {
                if ($row->external_id) {
                    if (isset($seenIds[$row->external_id])) {
                        $row->delete();
                        $removed++;
                    } else {
                        $seenIds[$row->external_id] = true;
                    }
                }
            }

            $totalVisible = ServerGame::where('is_visible', true)->count();
            $totalBytes = (int) ServerGame::where('is_visible', true)->sum('size_bytes');
            $totalGb = number_format($totalBytes / 1073741824, 1);

            DisklessServerConfig::updateOrCreate(
                ['key' => 'total_games'],
                ['value' => "{$totalVisible} Game Terdeteksi ({$totalGb} GB)"]
            );

            DisklessServerConfig::updateOrCreate(
                ['key' => 'last_update_log'],
                ['value' => "Scan Server selesai: {$totalVisible} game terpasang ({$added} baru, {$updated} diperbarui, {$removed} dihapus)."]
            );

            return [
                'total' => $totalVisible,
                'added' => $added,
                'updated' => $updated,
                'removed' => $removed,
                'total_gb' => $totalGb,
            ];
        } finally {
            $lock->release();
        }
    }

    protected static function upsertGame(array $data): string
    {
        $existing = ServerGame::where('external_id', $data['external_id'])->first();
        if (!$existing) {
            ServerGame::create($data);
            return 'created';
        }

        // Pertahankan tier, category, is_vault_game, is_visible yang sudah diatur admin kasir
        $existing->update([
            'name' => $data['name'],
            'publisher' => $data['publisher'],
            'launch_command' => $data['launch_command'],
            'launch_args' => $data['launch_args'],
            'install_path' => $data['install_path'],
            'size_bytes' => $data['size_bytes'],
        ]);

        return 'updated';
    }

    protected static function scanSteamLibraries(array $scanPaths): array
    {
        $vdfCandidates = [
            'C:\\Program Files (x86)\\Steam\\steamapps\\libraryfolders.vdf',
            'C:\\Program Files\\Steam\\steamapps\\libraryfolders.vdf',
            'D:\\Steam\\steamapps\\libraryfolders.vdf',
        ];

        $libraryDirs = [];
        foreach ($vdfCandidates as $vdf) {
            if (is_file($vdf)) {
                $content = @file_get_contents($vdf);
                if ($content && preg_match_all('/"path"\s+"([^"]+)"/i', $content, $matches)) {
                    foreach ($matches[1] as $rawDir) {
                        $clean = str_replace('\\\\', '\\', $rawDir);
                        $libraryDirs[strtolower(rtrim($clean, '\\/'))] = rtrim($clean, '\\/');
                    }
                }
            }
        }

        foreach ($scanPaths as $sp) {
            $cleanSp = rtrim($sp, '\\/');
            if (is_dir($cleanSp . '\\steamapps')) {
                $libraryDirs[strtolower($cleanSp)] = $cleanSp;
            }
        }

        $games = [];
        $seenAppIds = [];

        foreach ($libraryDirs as $libDir) {
            $steamAppsDir = $libDir . '\\steamapps';
            if (!is_dir($steamAppsDir)) {
                continue;
            }

            $manifests = @glob($steamAppsDir . '\\appmanifest_*.acf');
            if (!$manifests) {
                continue;
            }

            $driveLetter = strtoupper(substr($libDir, 0, 2));

            foreach ($manifests as $acfFile) {
                $acf = @file_get_contents($acfFile);
                if (!$acf) {
                    continue;
                }

                $appId = '';
                $name = '';
                $installDir = '';
                $sizeBytes = 0;

                if (preg_match('/"appid"\s+"(\d+)"/i', $acf, $m)) $appId = $m[1];
                if (preg_match('/"name"\s+"([^"]+)"/i', $acf, $m)) $name = $m[1];
                if (preg_match('/"installdir"\s+"([^"]+)"/i', $acf, $m)) $installDir = $m[1];
                if (preg_match('/"SizeOnDisk"\s+"(\d+)"/i', $acf, $m)) $sizeBytes = (int) $m[1];

                if (!$appId || !$name) {
                    continue;
                }
                if (isset($seenAppIds[$appId])) {
                    continue;
                }

                // Skip redistributables & runtime internal Steam
                $lowerName = strtolower($name);
                if (
                    $appId === '228980'
                    || str_contains($lowerName, 'redistributable')
                    || str_contains($lowerName, 'steam linux')
                    || str_contains($lowerName, 'proton')
                    || str_contains($lowerName, 'controller config')
                ) {
                    continue;
                }

                $seenAppIds[$appId] = true;
                $classInfo = self::classifyGame($name, $sizeBytes, 'steam');

                $games[] = [
                    'name' => $name,
                    'category' => $classInfo['category'],
                    'publisher' => "Steam Library ({$driveLetter})",
                    'source_type' => 'steam',
                    'external_id' => 'steam_' . $appId,
                    'launch_command' => 'steam://rungameid/' . $appId,
                    'launch_args' => null,
                    'install_path' => $steamAppsDir . '\\common\\' . $installDir,
                    'size_bytes' => $sizeBytes,
                    'min_tier' => $classInfo['min_tier'],
                    'is_vault_game' => $classInfo['is_vault_game'],
                    'is_visible' => true,
                ];
            }
        }

        return $games;
    }

    protected static function scanLocalFoldersAndLaunchers(array $scanPaths): array
    {
        $games = [];
        $seenExtIds = [];

        $addEntry = function (array $item) use (&$games, &$seenExtIds) {
            if (!isset($seenExtIds[$item['external_id']])) {
                $seenExtIds[$item['external_id']] = true;
                $games[] = $item;
            }
        };

        // A. Deteksi launcher game populer yang terpasang di server/PC
        $userProfile = getenv('USERPROFILE') ?: '';
        $appData = getenv('APPDATA') ?: '';

        $knownLaunchers = [
            [
                'id' => 'launcher_delta_force',
                'name' => 'Delta Force',
                'exe' => 'D:\\Delta Force\\launcher\\df_garena_launcher.exe',
                'args' => null,
                'install_path' => 'D:\\Delta Force',
                'category' => 'FPS TACTICAL',
                'publisher' => 'Garena / Team Jade',
                'min_tier' => 'vip',
                'source_type' => 'exe',
            ],
            [
                'id' => 'launcher_arknights_endfield',
                'name' => 'Arknights: Endfield',
                'exe' => 'C:\\Program Files\\GRYPHLINK\\Launcher.exe',
                'args' => '--game=EndField --reason=4',
                'install_path' => 'C:\\Program Files\\GRYPHLINK',
                'category' => 'OPEN WORLD RPG',
                'publisher' => 'GRYPHLINE',
                'min_tier' => 'vip',
                'source_type' => 'shortcut',
            ],
            [
                'id' => 'launcher_chunithm_xverse',
                'name' => 'Chunithm X-VERSE',
                'exe' => 'D:\\Chunithm X-VERSE\\Chunithm X-VERSE\\App\\game.bat',
                'args' => null,
                'install_path' => 'D:\\Chunithm X-VERSE',
                'category' => 'ARCADE RHYTHM',
                'publisher' => 'SEGA Arcade Local',
                'min_tier' => 'reguler',
                'source_type' => 'exe',
            ],
            [
                'id' => 'launcher_tlauncher',
                'name' => 'Minecraft (TLauncher)',
                'exe' => $appData . '\\.minecraft\\TLauncher.exe',
                'args' => null,
                'install_path' => $appData . '\\.minecraft',
                'category' => 'SANDBOX',
                'publisher' => 'TLauncher / Mojang',
                'min_tier' => 'reguler',
                'source_type' => 'shortcut',
            ],
            [
                'id' => 'launcher_valorant',
                'name' => 'Valorant',
                'exe' => 'C:\\Riot Games\\Riot Client\\RiotClientServices.exe',
                'args' => '--launch-product=valorant --launch-patchline=live',
                'install_path' => 'C:\\Riot Games',
                'category' => 'F2P ESPORTS FPS',
                'publisher' => 'Riot Games',
                'min_tier' => 'reguler',
                'source_type' => 'shortcut',
            ],
            [
                'id' => 'tool_obs_studio',
                'name' => 'OBS Studio (Live Streaming)',
                'exe' => 'C:\\Program Files\\obs-studio\\bin\\64bit\\obs64.exe',
                'args' => null,
                'install_path' => 'C:\\Program Files\\obs-studio',
                'category' => 'TOOLS & APP',
                'publisher' => 'OBS Project',
                'min_tier' => 'vip++',
                'source_type' => 'exe',
            ],
            [
                'id' => 'tool_steam_client',
                'name' => 'Steam Client',
                'exe' => 'C:\\Program Files (x86)\\Steam\\Steam.exe',
                'args' => null,
                'install_path' => 'C:\\Program Files (x86)\\Steam',
                'category' => 'TOOLS & APP',
                'publisher' => 'Valve Corporation',
                'min_tier' => 'reguler',
                'source_type' => 'exe',
            ],
        ];

        foreach ($knownLaunchers as $kl) {
            if ($kl['exe'] && is_file($kl['exe'])) {
                $size = self::estimateQuickDirSize($kl['install_path']);
                $addEntry([
                    'name' => $kl['name'],
                    'category' => $kl['category'],
                    'publisher' => $kl['publisher'],
                    'source_type' => $kl['source_type'],
                    'external_id' => $kl['id'],
                    'launch_command' => $kl['exe'],
                    'launch_args' => $kl['args'],
                    'install_path' => $kl['install_path'],
                    'size_bytes' => $size,
                    'min_tier' => $kl['min_tier'],
                    'is_vault_game' => false,
                    'is_visible' => true,
                ]);
            }
        }

        // B. Scan direktori kustom/diskless (misal D:\Games, C:\Games, dll)
        foreach ($scanPaths as $rawDir) {
            $dir = rtrim($rawDir, '\\/');
            if (!is_dir($dir)) {
                continue;
            }
            $lowerDir = strtolower($dir);
            if (str_contains($lowerDir, 'steam') || str_contains($lowerDir, 'delta force') || str_contains($lowerDir, 'chunithm')) {
                continue; // Sudah ditangani
            }

            $entries = @scandir($dir);
            if (!$entries) {
                continue;
            }

            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $subDir = $dir . '\\' . $entry;
                if (!is_dir($subDir)) {
                    continue;
                }

                $mainExe = self::findBestExecutable($subDir, $entry);
                if (!$mainExe) {
                    continue;
                }

                $extId = 'local_' . md5(strtolower($subDir));
                $size = self::estimateQuickDirSize($subDir);
                $classInfo = self::classifyGame($entry, $size, 'exe');

                $addEntry([
                    'name' => $entry,
                    'category' => $classInfo['category'],
                    'publisher' => 'Disk Lokal (' . strtoupper(substr($dir, 0, 2)) . ')',
                    'source_type' => 'exe',
                    'external_id' => $extId,
                    'launch_command' => $mainExe,
                    'launch_args' => null,
                    'install_path' => $subDir,
                    'size_bytes' => $size,
                    'min_tier' => $classInfo['min_tier'],
                    'is_vault_game' => $classInfo['is_vault_game'],
                    'is_visible' => true,
                ]);
            }
        }

        return $games;
    }

    protected static function findBestExecutable(string $dir, string $folderName): ?string
    {
        $files = @glob($dir . '\\*.exe') ?: [];
        $bats = @glob($dir . '\\*.bat') ?: [];
        $candidates = array_merge($files, $bats);

        $blacklist = ['unins', 'crash', 'redist', 'dxsetup', 'vc_redist', 'helper', 'report', 'update', 'setup', 'hwinfo'];
        $filtered = [];

        foreach ($candidates as $f) {
            $base = strtolower(basename($f));
            $skip = false;
            foreach ($blacklist as $bad) {
                if (str_contains($base, $bad)) {
                    $skip = true;
                    break;
                }
            }
            if (!$skip) {
                $filtered[] = $f;
            }
        }

        if (empty($filtered)) {
            return null;
        }

        $folderSlug = strtolower(preg_replace('/[^a-z0-9]/i', '', $folderName));
        foreach ($filtered as $f) {
            $baseSlug = strtolower(preg_replace('/[^a-z0-9]/i', '', pathinfo($f, PATHINFO_FILENAME)));
            if ($baseSlug === $folderSlug || str_contains($baseSlug, $folderSlug) || str_contains($baseSlug, 'launcher') || str_contains($baseSlug, 'game')) {
                return $f;
            }
        }

        return $filtered[0];
    }

    protected static function estimateQuickDirSize(string $dir): int
    {
        if (!is_dir($dir)) {
            return 0;
        }
        $total = 0;
        $queue = [[$dir, 0]];
        $filesScanned = 0;

        while (!empty($queue) && $filesScanned < 4000) {
            [$current, $depth] = array_shift($queue);
            $items = @scandir($current);
            if (!$items) continue;

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') continue;
                $path = $current . '\\' . $item;
                if (is_file($path)) {
                    $total += (int) (@filesize($path) ?: 0);
                    $filesScanned++;
                } elseif (is_dir($path) && $depth < 3) {
                    $queue[] = [$path, $depth + 1];
                }
            }
        }

        return $total;
    }

    protected static function classifyGame(string $name, int $sizeBytes, string $sourceType): array
    {
        $lower = strtolower($name);
        $gb = $sizeBytes / 1073741824;

        // Cek apakah ada di GameVaultAccount (portable across SQLite/MySQL)
        $isVault = false;
        foreach (GameVaultAccount::all() as $va) {
            $vLower = strtolower($va->game_title);
            if (str_contains($vLower, $lower) || str_contains($lower, $vLower)) {
                $isVault = true;
                break;
            }
            if (str_contains($lower, 'grand theft auto') && str_contains($vLower, 'grand theft auto')) {
                $isVault = true;
                break;
            }
        }

        if (
            str_contains($lower, 'wallpaper engine')
            || str_contains($lower, 'vtube studio')
            || str_contains($lower, 'obs')
            || str_contains($lower, 'task bar hero')
            || str_contains($lower, 'lossless')
        ) {
            return [
                'category' => 'TOOLS & APP',
                'min_tier' => 'reguler',
                'is_vault_game' => false,
            ];
        }

        if (
            str_contains($lower, 'forza horizon')
            || str_contains($lower, 'cyberpunk')
            || str_contains($lower, 'black myth')
            || str_contains($lower, 'red dead')
            || str_contains($lower, 'monster hunter')
            || $gb >= 140
        ) {
            return [
                'category' => 'AAA ULTRA',
                'min_tier' => 'vip++',
                'is_vault_game' => $isVault || str_contains($lower, 'forza') || str_contains($lower, 'cyberpunk') || str_contains($lower, 'black myth') || str_contains($lower, 'red dead'),
            ];
        }

        if (
            str_contains($lower, 'grand theft auto')
            || str_contains($lower, 'gta')
            || str_contains($lower, 'delta force')
            || str_contains($lower, 'subnautica')
            || str_contains($lower, 'green hell')
            || str_contains($lower, 'raft')
            || str_contains($lower, 'peak')
            || str_contains($lower, 'heartopia')
            || $gb >= 10
        ) {
            // Kecuali game kasual F2P besar (seperti Blue Archive / War Thunder)
            if (str_contains($lower, 'blue archive') || str_contains($lower, 'war thunder')) {
                return [
                    'category' => str_contains($lower, 'war thunder') ? 'F2P ESPORTS' : 'ANIME RPG',
                    'min_tier' => 'reguler',
                    'is_vault_game' => false,
                ];
            }

            return [
                'category' => 'ACTION / OPEN WORLD',
                'min_tier' => 'vip',
                'is_vault_game' => $isVault || str_contains($lower, 'grand theft auto'),
            ];
        }

        return [
            'category' => 'CASUAL / INDIE',
            'min_tier' => 'reguler',
            'is_vault_game' => false,
        ];
    }
}
