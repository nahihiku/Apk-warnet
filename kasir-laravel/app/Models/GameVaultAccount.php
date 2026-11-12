<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameVaultAccount extends Model
{
    protected $guarded = [];

    protected $casts = [
        'borrowed_at' => 'datetime',
    ];
}
