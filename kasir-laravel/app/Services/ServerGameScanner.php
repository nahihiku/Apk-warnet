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
        return 'D:\\Games;C:\\Games';
    }

    /**
     * Memindai seluruh game yang benar-benar terpasang di PC Server secara universal
     * (Steam Registry + VDF, Epic Games Manifests, Riot Client Metadata, Drive Diskless, & Desktop Shortcuts)
     * tanpa hardcode path spesifik perangkat.
     */
    public static function scan(bool $pruneMissing = true): array
    {
        $lock = Cache::lock('server_game_scan_lock', 25);
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

            $configuredPaths = array_filter(array_map('trim', explode(';', $rawPaths)));
            $envInfo = self::inspectWindowsEnvironment();

            $discoveredExternalIds = [];
            $seenCommands = [];
            $added = 0;
            $updated = 0;

            $processBatch = function (array $batch) use (&$discoveredExternalIds, &$seenCommands, &$added, &$updated) {
                foreach ($batch as $g) {
                    $cmdKey = strtolower(trim($g['launch_command']) . '|' . trim((string) ($g['launch_args'] ?? '')));
                    if (isset($seenCommands[$cmdKey])) {
                        continue;
                    }
                    $seenCommands[$cmdKey] = true;
                    $discoveredExternalIds[] = $g['external_id'];
                    $res = self::upsertGame($g);
                    if ($res === 'created') $added++;
                    elseif ($res === 'updated') $updated++;
                }
            };

            // 1. Steam Libraries (via Windows Registry + libraryfolders.vdf + custom scan paths)
            $processBatch(self::scanSteamLibraries($envInfo['steam_path'] ?? null, $envInfo['drives'] ?? [], $configuredPaths));

            // 2. Epic Games Store Manifests (C:\ProgramData\Epic\EpicGamesLauncher\Data\Manifests)
            $processBatch(self::scanEpicManifests());

            // 3. Riot Games Metadata (C:\ProgramData\Riot Games)
            $processBatch(self::scanRiotGames());

            // 4. Folder Game Lokal & Diskless (\Games, \Game, \GameOffline di semua drive + configured scan_paths)
            $processBatch(self::scanGameDirectories($envInfo['drives'] ?? [], $configuredPaths));

            // 5. Shortcut Game Desktop (.lnk & .url via WScript.Shell, mengecualikan utilitas non-game)
            $processBatch(self::scanDesktopShortcuts($envInfo['shortcuts'] ?? []));

            // 6. Bersihkan game hasil auto-scan yang sudah di-uninstall atau duplikat
            $removed = 0;
            if ($pruneMissing && count($discoveredExternalIds) > 0) {
                $removed = ServerGame::whereIn('source_type', ['steam', 'epic', 'riot', 'exe', 'shortcut'])
                    ->whereNotIn('external_id', $discoveredExternalIds)
                    ->delete();
            }

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

        // Pertahankan pengaturan min_tier, category, is_vault_game, is_visible yang sudah dikurasi admin kasir
        $existing->update([
            'name' => $data['name'],
            'publisher' => $data['publisher'],
            'launch_command' => $data['launch_command'],
            'launch_args' => $data['launch_args'],
            'install_path' => $data['install_path'],
            'size_bytes' => $data['size_bytes'] > 0 ? $data['size_bytes'] : $existing->size_bytes,
        ]);

        return 'updated';
    }

    /**
     * Kueri satu kali ke Windows untuk mendapatkan drive terpasang, path Steam dari Registry, dan shortcut Desktop.
     */
    protected static function inspectWindowsEnvironment(): array
    {
        $psScript = <<<'PS'
$ProgressPreference = 'SilentlyContinue'
$drives = @([System.IO.DriveInfo]::GetDrives() | Where-Object { $_.DriveType -in @('Fixed','Network') -and $_.IsReady } | ForEach-Object { $_.Name.TrimEnd('\') })
$steamReg = (Get-ItemProperty -Path 'HKCU:\Software\Valve\Steam' -Name 'SteamPath' -ErrorAction SilentlyContinue).SteamPath
if (-not $steamReg) { $steamReg = (Get-ItemProperty -Path 'HKLM:\SOFTWARE\WOW6432Node\Valve\Steam' -Name 'InstallPath' -ErrorAction SilentlyContinue).InstallPath }
if (-not $steamReg) { $steamReg = (Get-ItemProperty -Path 'HKLM:\SOFTWARE\Valve\Steam' -Name 'InstallPath' -ErrorAction SilentlyContinue).InstallPath }

$sh = New-Object -ComObject WScript.Shell
$shortcuts = @()
$desktopDirs = @()
if ($env:USERPROFILE) { $desktopDirs += ($env:USERPROFILE + '\Desktop') }
if ($env:PUBLIC) { $desktopDirs += ($env:PUBLIC + '\Desktop') } else { $desktopDirs += 'C:\Users\Public\Desktop' }

foreach ($dir in ($desktopDirs | Select-Object -Unique)) {
    if (Test-Path $dir) {
        Get-ChildItem -Path $dir -Filter '*.lnk' -ErrorAction SilentlyContinue | ForEach-Object {
            try {
                $sc = $sh.CreateShortcut($_.FullName)
                if ($sc.TargetPath) {
                    $shortcuts += [PSCustomObject]@{
                        name = $_.BaseName
                        target = $sc.TargetPath
                        args = $sc.Arguments
                        type = 'lnk'
                    }
                }
            } catch {}
        }
        Get-ChildItem -Path $dir -Filter '*.url' -ErrorAction SilentlyContinue | ForEach-Object {
            try {
                $raw = Get-Content $_.FullName -Raw -ErrorAction SilentlyContinue
                if ($raw -match 'URL=(.+)') {
                    $shortcuts += [PSCustomObject]@{
                        name = $_.BaseName
                        target = $matches[1].Trim()
                        args = ''
                        type = 'url'
                    }
                }
            } catch {}
        }
    }
}

[PSCustomObject]@{
    drives = $drives
    steam_path = $steamReg
    shortcuts = $shortcuts
} | ConvertTo-Json -Depth 4 -Compress
PS;

        $encoded = base64_encode(mb_convert_encoding($psScript, 'UTF-16LE', 'UTF-8'));
        $out = @shell_exec("powershell -NoProfile -NonInteractive -EncodedCommand {$encoded}");
        if (!$out) {
            return ['drives' => ['C:', 'D:'], 'steam_path' => null, 'shortcuts' => []];
        }

        $decoded = json_decode(trim($out), true);
        if (!is_array($decoded)) {
            return ['drives' => ['C:', 'D:'], 'steam_path' => null, 'shortcuts' => []];
        }

        return $decoded;
    }

    protected static function scanSteamLibraries(?string $regSteamPath, array $drives, array $configuredPaths): array
    {
        $vdfCandidates = [];
        if ($regSteamPath) {
            $cleanReg = rtrim(str_replace('/', '\\', $regSteamPath), '\\/');
            $vdfCandidates[] = $cleanReg . '\\steamapps\\libraryfolders.vdf';
        }
        foreach ($drives as $drv) {
            $vdfCandidates[] = $drv . '\\Program Files (x86)\\Steam\\steamapps\\libraryfolders.vdf';
            $vdfCandidates[] = $drv . '\\Steam\\steamapps\\libraryfolders.vdf';
        }

        $libraryDirs = [];
        foreach (array_unique($vdfCandidates) as $vdf) {
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

        // Cek juga folder SteamLibrary standar di drive manapun & configuredPaths
        foreach ($drives as $drv) {
            foreach (['SteamLibrary', 'Games\\SteamLibrary'] as $suffix) {
                $cand = $drv . '\\' . $suffix;
                if (is_dir($cand . '\\steamapps')) {
                    $libraryDirs[strtolower($cand)] = $cand;
                }
            }
        }
        foreach ($configuredPaths as $sp) {
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

                if (!$appId || !$name || isset($seenAppIds[$appId])) {
                    continue;
                }

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
                $classInfo = self::classifyGame($name, $sizeBytes);

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

    protected static function scanEpicManifests(): array
    {
        $manifestDir = 'C:\\ProgramData\\Epic\\EpicGamesLauncher\\Data\\Manifests';
        if (!is_dir($manifestDir)) {
            return [];
        }

        $items = @glob($manifestDir . '\\*.item') ?: [];
        $games = [];

        foreach ($items as $file) {
            $json = @json_decode(@file_get_contents($file), true);
            if (!is_array($json) || empty($json['DisplayName']) || empty($json['AppName'])) {
                continue;
            }
            if (!empty($json['bIsIncompleteInstall'])) {
                continue;
            }

            $name = $json['DisplayName'];
            $sizeBytes = (int) ($json['InstallSize'] ?? 0);
            $installLoc = $json['InstallLocation'] ?? '';
            $classInfo = self::classifyGame($name, $sizeBytes);

            $games[] = [
                'name' => $name,
                'category' => $classInfo['category'],
                'publisher' => 'Epic Games Store',
                'source_type' => 'epic',
                'external_id' => 'epic_' . $json['AppName'],
                'launch_command' => 'com.epicgames.launcher://apps/' . $json['AppName'] . '?action=launch&silent=true',
                'launch_args' => null,
                'install_path' => $installLoc,
                'size_bytes' => $sizeBytes,
                'min_tier' => $classInfo['min_tier'],
                'is_vault_game' => $classInfo['is_vault_game'],
                'is_visible' => true,
            ];
        }

        return $games;
    }

    protected static function scanRiotGames(): array
    {
        $installsFile = 'C:\\ProgramData\\Riot Games\\RiotClientInstalls.json';
        $metaDir = 'C:\\ProgramData\\Riot Games\\Metadata';
        if (!is_file($installsFile) && !is_dir($metaDir)) {
            return [];
        }

        $riotClientExe = null;
        if (is_file($installsFile)) {
            $json = @json_decode(@file_get_contents($installsFile), true);
            $riotClientExe = $json['rc_live'] ?? $json['rc_default'] ?? null;
        }
        if (!$riotClientExe || !is_file($riotClientExe)) {
            return [];
        }

        $games = [];
        if (is_dir($metaDir)) {
            $dirs = @scandir($metaDir) ?: [];
            foreach ($dirs as $d) {
                if ($d === '.' || $d === '..' || !str_contains($d, '.live')) {
                    continue;
                }
                $product = str_replace('.live', '', $d);
                if (in_array($product, ['riot_client', 'keystone'])) {
                    continue;
                }
                $displayName = ucwords(str_replace('_', ' ', $product));
                if ($product === 'valorant') $displayName = 'VALORANT';
                if ($product === 'league_of_legends') $displayName = 'League of Legends';

                $classInfo = self::classifyGame($displayName, 0);
                $games[] = [
                    'name' => $displayName,
                    'category' => 'F2P ESPORTS',
                    'publisher' => 'Riot Games',
                    'source_type' => 'riot',
                    'external_id' => 'riot_' . $product,
                    'launch_command' => $riotClientExe,
                    'launch_args' => "--launch-product={$product} --launch-patchline=live",
                    'install_path' => dirname($riotClientExe),
                    'size_bytes' => 0,
                    'min_tier' => 'reguler',
                    'is_vault_game' => $classInfo['is_vault_game'],
                    'is_visible' => true,
                ];
            }
        }

        return $games;
    }

    protected static function scanGameDirectories(array $drives, array $configuredPaths): array
    {
        $dirCandidates = [];
        foreach ($drives as $drv) {
            foreach (['Games', 'Game', 'GameOnline', 'GameOffline'] as $std) {
                $cand = $drv . '\\' . $std;
                if (is_dir($cand)) {
                    $dirCandidates[strtolower($cand)] = $cand;
                }
            }
        }
        foreach ($configuredPaths as $cp) {
            $clean = rtrim($cp, '\\/');
            if (is_dir($clean) && !is_dir($clean . '\\steamapps')) {
                $dirCandidates[strtolower($clean)] = $clean;
            }
        }

        $games = [];
        foreach ($dirCandidates as $rootDir) {
            // Cek apakah rootDir sendiri merupakan satu folder game langsung (berisi exe/bat game)
            $directExe = self::findBestExecutable($rootDir, basename($rootDir));
            $subEntries = @scandir($rootDir) ?: [];
            $hasSubGameDirs = false;

            foreach ($subEntries as $entry) {
                if ($entry === '.' || $entry === '..') continue;
                $subDir = $rootDir . '\\' . $entry;
                if (!is_dir($subDir)) continue;

                // Cek subfolder hingga kedalaman 2 untuk game dengan struktur <Game>\App\game.bat atau <Game>\launcher\...
                $subExe = self::findBestExecutable($subDir, $entry);
                if (!$subExe) {
                    $subExe = self::findExecutableOneLevelDeeper($subDir, $entry);
                }
                if (!$subExe) continue;

                $hasSubGameDirs = true;
                $size = self::estimateQuickDirSize($subDir);
                $classInfo = self::classifyGame($entry, $size);

                $games[] = [
                    'name' => $entry,
                    'category' => $classInfo['category'],
                    'publisher' => 'Drive Diskless (' . strtoupper(substr($rootDir, 0, 2)) . ')',
                    'source_type' => 'exe',
                    'external_id' => 'dir_' . md5(strtolower($subExe)),
                    'launch_command' => $subExe,
                    'launch_args' => null,
                    'install_path' => $subDir,
                    'size_bytes' => $size,
                    'min_tier' => $classInfo['min_tier'],
                    'is_vault_game' => $classInfo['is_vault_game'],
                    'is_visible' => true,
                ];
            }

            // Jika folder yang didaftarkan admin di scan_paths adalah folder 1 game tunggal
            if (!$hasSubGameDirs && $directExe) {
                $folderName = basename($rootDir);
                $size = self::estimateQuickDirSize($rootDir);
                $classInfo = self::classifyGame($folderName, $size);

                $games[] = [
                    'name' => $folderName,
                    'category' => $classInfo['category'],
                    'publisher' => 'Drive Diskless (' . strtoupper(substr($rootDir, 0, 2)) . ')',
                    'source_type' => 'exe',
                    'external_id' => 'dir_' . md5(strtolower($directExe)),
                    'launch_command' => $directExe,
                    'launch_args' => null,
                    'install_path' => $rootDir,
                    'size_bytes' => $size,
                    'min_tier' => $classInfo['min_tier'],
                    'is_vault_game' => $classInfo['is_vault_game'],
                    'is_visible' => true,
                ];
            }
        }

        return $games;
    }

    protected static function scanDesktopShortcuts(array $shortcuts): array
    {
        if (empty($shortcuts)) {
            return [];
        }

        $nonGameKeywords = [
            'chrome', 'edge', 'brave', 'firefox', 'opera', 'thunderbird', 'vlc',
            'virtualbox', 'vmware', 'metatrader', 'metaeditor', 'sap ', 'easeus',
            'ultraviewer', 'avica', 'teamviewer', 'anydesk', 'rustdesk', 'hwmonitor',
            'cpu-z', 'gpu-z', 'mouse without borders', 'nvidia', 'antigravity',
            'vscode', 'visual studio', 'notepad', 'word', 'excel', 'powerpoint',
            'outlook', 'onenote', 'acrobat', 'foxit', 'winrar', '7-zip', 'zoom',
            'postman', 'docker', 'laragon', 'xampp', 'installer', 'uninst', 'cmd',
            'powershell', 'terminal', 'recycle', 'internet explorer', 'gryphlink',
        ];

        $games = [];
        $seenTargets = [];

        foreach ($shortcuts as $sc) {
            $name = trim($sc['name'] ?? '');
            $target = trim($sc['target'] ?? '');
            $args = trim($sc['args'] ?? '');
            $type = $sc['type'] ?? 'lnk';

            if (!$name || !$target) continue;

            // Lewati URL browser biasa http/https, kecuali protokol launcher (steam://, com.epicgames, roblox://, dll)
            $lowerTarget = strtolower($target);
            if ($type === 'url' && (str_starts_with($lowerTarget, 'http://') || str_starts_with($lowerTarget, 'https://'))) {
                continue;
            }
            if (str_contains($lowerTarget, 'steam://rungameid/')) {
                continue; // Sudah ditangani penuh oleh Steam manifest scanner beserta ukuran file aslinya
            }
            if ($type === 'lnk' && !is_file($target)) {
                continue;
            }
            if (str_contains($lowerTarget, '\\windows\\installer\\')) {
                continue;
            }

            $lowerName = strtolower($name);
            $isBlacklisted = false;
            foreach ($nonGameKeywords as $bad) {
                if (str_contains($lowerName, $bad) || str_contains($lowerTarget, $bad)) {
                    // Izinkan pengecualian jika argumen menunjuk ke game spesifik (misal GRYPHLINK Launcher --game=EndField)
                    if ($bad === 'gryphlink' && str_contains(strtolower($args), '--game=')) {
                        continue;
                    }
                    $isBlacklisted = true;
                    break;
                }
            }
            if ($isBlacklisted) continue;

            $targetKey = strtolower($target . ' ' . $args);
            if (isset($seenTargets[$targetKey])) continue;
            $seenTargets[$targetKey] = true;

            $installDir = ($type === 'lnk') ? dirname($target) : '';
            // Gunakan parent dir jika exe berada di dalam subfolder launcher / bin / app
            if ($installDir) {
                $baseFolder = strtolower(basename($installDir));
                if (in_array($baseFolder, ['launcher', 'bin', 'bin64', '64bit', 'app'])) {
                    $parent = dirname($installDir);
                    if (strlen($parent) > 4) {
                        $installDir = $parent;
                    }
                }
                if (in_array(strtolower(basename($installDir)), ['bin', 'bin64', '64bit'])) {
                    $installDir = dirname($installDir);
                }
            }

            $size = $installDir ? self::estimateQuickDirSize($installDir) : 0;
            $classInfo = self::classifyGame($name, $size);

            $games[] = [
                'name' => $name,
                'category' => $classInfo['category'],
                'publisher' => 'Desktop Launcher (' . ($installDir ? strtoupper(substr($installDir, 0, 2)) : 'URL') . ')',
                'source_type' => 'shortcut',
                'external_id' => 'sc_' . md5($targetKey),
                'launch_command' => $target,
                'launch_args' => $args !== '' ? $args : null,
                'install_path' => $installDir,
                'size_bytes' => $size,
                'min_tier' => $classInfo['min_tier'],
                'is_vault_game' => $classInfo['is_vault_game'],
                'is_visible' => true,
            ];
        }

        return $games;
    }

    protected static function findExecutableOneLevelDeeper(string $dir, string $gameName): ?string
    {
        $subs = @scandir($dir) ?: [];
        foreach ($subs as $sub) {
            if ($sub === '.' || $sub === '..') continue;
            $subPath = $dir . '\\' . $sub;
            if (is_dir($subPath)) {
                $exe = self::findBestExecutable($subPath, $gameName);
                if ($exe) return $exe;
                $deeper = @scandir($subPath) ?: [];
                foreach ($deeper as $d) {
                    if ($d === '.' || $d === '..') continue;
                    $dPath = $subPath . '\\' . $d;
                    if (is_dir($dPath) && in_array(strtolower($d), ['app', 'launcher', 'bin', 'win64'])) {
                        $exe = self::findBestExecutable($dPath, $gameName);
                        if ($exe) return $exe;
                    }
                }
            }
        }
        return null;
    }

    protected static function findBestExecutable(string $dir, string $folderName): ?string
    {
        $files = @glob($dir . '\\*.exe') ?: [];
        $bats = @glob($dir . '\\*.bat') ?: [];
        $candidates = array_merge($files, $bats);

        $blacklist = ['unins', 'crash', 'redist', 'dxsetup', 'vc_redist', 'helper', 'report', 'update', 'setup', 'hwinfo', 'service', 'bootstrap'];
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

    /**
     * Klasifikasi kategori & kelas PC secara generik berbasis pola utilitas & ukuran disk.
     * Tidak bergantung pada nama game tertentu; admin tetap bisa mengubah tiap game dari Dashboard Kasir.
     */
    protected static function classifyGame(string $name, int $sizeBytes): array
    {
        $lower = strtolower($name);
        $gb = $sizeBytes / 1073741824;

        $isVault = false;
        foreach (GameVaultAccount::all() as $va) {
            $vLower = strtolower($va->game_title);
            if (str_contains($vLower, $lower) || str_contains($lower, $vLower)) {
                $isVault = true;
                break;
            }
        }

        if (
            str_contains($lower, 'wallpaper engine')
            || str_contains($lower, 'vtube studio')
            || str_contains($lower, 'obs')
            || str_contains($lower, 'overwolf')
            || str_contains($lower, 'task bar hero')
            || str_contains($lower, 'lossless')
            || $lower === 'steam'
            || str_contains($lower, 'steam client')
            || str_contains($lower, 'epic games launcher')
        ) {
            return [
                'category' => 'TOOLS & APP',
                'min_tier' => str_contains($lower, 'obs') ? 'vip++' : 'reguler',
                'is_vault_game' => false,
            ];
        }

        if ($gb >= 120) {
            return [
                'category' => 'AAA HEAVY',
                'min_tier' => 'vip++',
                'is_vault_game' => $isVault,
            ];
        }

        if ($gb >= 12) {
            return [
                'category' => 'HD ACTION / RPG',
                'min_tier' => 'vip',
                'is_vault_game' => $isVault,
            ];
        }

        return [
            'category' => 'PC GAME',
            'min_tier' => 'reguler',
            'is_vault_game' => $isVault,
        ];
    }
}
