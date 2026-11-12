<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_vault_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('game_title');
            $table->string('platform')->default('steam'); // steam, gamepass, ea_play
            $table->string('account_username');
            $table->string('account_token')->nullable(); // Digunakan untuk auto-inject kredensial tanpa terlihat user
            $table->string('status')->default('available'); // available, borrowed
            $table->string('borrowed_by_pc')->nullable(); // PC-13, dsb.
            $table->string('borrowed_by_member')->nullable();
            $table->timestamp('borrowed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('diskless_server_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diskless_server_configs');
        Schema::dropIfExists('game_vault_accounts');
    }
};
