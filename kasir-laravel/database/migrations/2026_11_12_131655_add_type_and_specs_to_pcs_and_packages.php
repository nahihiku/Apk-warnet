<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pc_clients', function (Blueprint $table) {
            $table->string('pc_type')->default('reguler')->after('name'); // reguler, vip, vip++
            $table->bigInteger('hourly_rate')->default(5000)->after('pc_type');
            $table->string('specs')->nullable()->after('hourly_rate');
        });

        Schema::table('billing_packages', function (Blueprint $table) {
            $table->string('pc_type')->default('all')->after('duration_minutes'); // all, reguler, vip, vip++
        });
    }

    public function down(): void
    {
        Schema::table('billing_packages', function (Blueprint $table) {
            $table->dropColumn('pc_type');
        });

        Schema::table('pc_clients', function (Blueprint $table) {
            $table->dropColumn(['pc_type', 'hourly_rate', 'specs']);
        });
    }
};
