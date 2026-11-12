<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisklessServerConfig extends Model
{
    protected $guarded = [];

    /**
     * Normalize a Windows local path (preserve trailing backslash for drive roots like D:\).
     */
    public static function normalizeWindowsPath(string $path, string $fallback): string
    {
        $clean = trim(str_replace('/', '\\', $path));
        if ($clean === '') {
            $clean = $fallback;
        }
        $clean = rtrim($clean, '\\');
        if (strlen($clean) === 2 && $clean[1] === ':') {
            $clean .= '\\';
        }
        return $clean;
    }

    /**
     * Normalize a Windows drive letter input into "X:" format.
     */
    public static function normalizeDriveLetter(?string $drive, string $fallback): string
    {
        $clean = strtoupper(trim((string) $drive));
        if (preg_match('/^[D-Z]/', $clean, $m)) {
            return $m[0] . ':';
        }
        return $fallback;
    }

    /**
     * Build the server game share configuration for both the Kasir UI and C++ Client API.
     */
    public static function getGameShareConfig(?string $requestHost = null): array
    {
        $map = static::pluck('value', 'key')->toArray();

        $enabled = ($map['share_enabled'] ?? '1') !== '0';
        $sourcePath = self::normalizeWindowsPath((string) ($map['share_source_path'] ?? ''), 'D:\\');
        $shareName = trim((string) ($map['share_name'] ?? ''));
        if ($shareName === '') {
            $shareName = 'UkikuraGames';
        }

        $clientDrive = self::normalizeDriveLetter($map['client_drive'] ?? null, 'G:');
        $customUnc = trim((string) ($map['share_unc'] ?? ''));
        $host = trim((string) $requestHost) ?: '127.0.0.1';
        $shareUnc = $customUnc !== '' ? $customUnc : "\\\\{$host}\\{$shareName}";

        $extraSourcePath = self::normalizeWindowsPath((string) ($map['extra_source_path'] ?? ''), 'C:\\Games');
        $extraShareName = trim((string) ($map['extra_share_name'] ?? ''));
        if ($extraShareName === '') {
            $extraShareName = 'UkikuraCGames';
        }
        $extraDrive = self::normalizeDriveLetter($map['extra_client_drive'] ?? null, 'H:');
        $extraUnc = "\\\\{$host}\\{$extraShareName}";

        return [
            'enabled' => $enabled,
            'source_path' => $sourcePath,
            'share_name' => $shareName,
            'share_unc' => $shareUnc,
            'vbox_unc' => "\\\\VBoxSvr\\{$shareName}",
            'custom_unc' => $customUnc,
            'client_drive' => $clientDrive,
            'share_user' => trim((string) ($map['share_user'] ?? '')),
            'share_pass' => (string) ($map['share_pass'] ?? ''),
            'extra_source_path' => $extraSourcePath,
            'extra_share_name' => $extraShareName,
            'extra_share_unc' => $extraUnc,
            'extra_vbox_unc' => "\\\\VBoxSvr\\{$extraShareName}",
            'extra_drive' => $extraDrive,
        ];
    }

    /**
     * Inspect active Windows SMB shares on the server host without requiring elevation.
     */
    public static function detectWindowsShareStatus(string $shareName, string $extraShareName = ''): array
    {
        $status = [
            'primary_active' => false,
            'extra_active' => false,
            'shares' => [],
        ];

        if (PHP_OS_FAMILY !== 'Windows') {
            return $status;
        }

        $output = @shell_exec('net share 2>nul');
        if (!is_string($output) || trim($output) === '') {
            return $status;
        }

        $lines = preg_split('/\r?\n/', $output) ?: [];
        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '' || str_starts_with($trim, '-') || str_starts_with($trim, 'Share name') || str_starts_with($trim, 'The command')) {
                continue;
            }
            if (preg_match('/^([^\s]+)\s+(.+?)(?:\s{2,}.*)?$/', $trim, $m)) {
                $name = $m[1];
                $res = trim($m[2]);
                $status['shares'][$name] = $res;
                if (strcasecmp($name, $shareName) === 0) {
                    $status['primary_active'] = true;
                }
                if ($extraShareName !== '' && strcasecmp($name, $extraShareName) === 0) {
                    $status['extra_active'] = true;
                }
            }
        }

        return $status;
    }
}
