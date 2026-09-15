<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * План promo есть в config/tariffs.php, но выпал из enum: без него
     * администратор не может назначить промо-тариф из админки.
     */
    public function up(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->enum('plan', ['free', 'paid', 'promo'])->default('free')->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->enum('plan', ['free', 'paid'])->default('free')->change();
        });
    }
};
