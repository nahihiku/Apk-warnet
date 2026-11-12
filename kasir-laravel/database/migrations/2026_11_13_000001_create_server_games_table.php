<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_games', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->default('PC GAME');
            $table->string('publisher')->default('Server Local');
            $table->string('source_type')->default('steam'); // steam, exe, shortcut, custom
            $table->string('external_id')->nullable()->index();
            $table->text('launch_command');
            $table->text('launch_args')->nullable();
            $table->text('install_path')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('min_tier')->default('reguler'); // reguler, vip, vip++
            $table->boolean('is_vault_game')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_games');
    }
};
