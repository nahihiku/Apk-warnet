<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerGame extends Model
{
    protected $fillable = [
        'name',
        'category',
        'publisher',
        'source_type',
        'external_id',
        'launch_command',
        'launch_args',
        'install_path',
        'size_bytes',
        'min_tier',
        'is_vault_game',
        'is_visible',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'is_vault_game' => 'boolean',
        'is_visible' => 'boolean',
    ];

    protected $appends = ['size_formatted'];

    public function getSizeFormattedAttribute(): string
    {
        $bytes = (int) $this->size_bytes;
        if ($bytes <= 0) {
            return 'Launcher';
        }
        $gb = $bytes / 1073741824;
        if ($gb >= 1) {
            return number_format($gb, 1) . ' GB';
        }
        $mb = $bytes / 1048576;
        return number_format($mb, 0) . ' MB';
    }
}
