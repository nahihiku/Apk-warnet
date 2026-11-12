<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('name');
            $table->string('password');
            $table->bigInteger('balance')->default(0);
            $table->integer('saved_seconds')->default(0);
            $table->timestamps();
        });

        Schema::create('pc_clients', function (Blueprint $table) {
            $table->id();
            $table->string('pc_code')->unique(); // e.g. PC-01
            $table->string('name');
            $table->string('status')->default('locked'); // locked, active
            $table->boolean('is_locked')->default(true);
            $table->foreignId('current_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('session_end_at')->nullable();
            $table->integer('remaining_seconds_cache')->default(0);
            $table->string('admin_message')->nullable();
            $table->integer('message_id')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('duration_minutes');
            $table->bigInteger('price');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('food_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->default('makanan'); // makanan, minuman, snack
            $table->bigInteger('price');
            $table->integer('stock')->default(50);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->string('order_type')->default('food'); // food, billing
            $table->string('pc_code')->default('KASIR');
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('billing_package_id')->nullable()->constrained('billing_packages')->nullOnDelete();
            $table->bigInteger('total_price')->default(0);
            $table->string('payment_method')->default('balance'); // balance, qris, cash
            $table->string('payment_status')->default('paid'); // paid, pending
            $table->string('kitchen_status')->default('waiting'); // waiting, cooking, ready, completed, none
            $table->string('notes')->nullable();
            $table->string('items_summary')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('food_menu_id')->nullable()->constrained('food_menus')->nullOnDelete();
            $table->string('menu_name');
            $table->integer('qty')->default(1);
            $table->bigInteger('price')->default(0);
            $table->bigInteger('subtotal')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('food_menus');
        Schema::dropIfExists('billing_packages');
        Schema::dropIfExists('pc_clients');
        Schema::dropIfExists('members');
    }
};
