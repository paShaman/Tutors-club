<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Черновик пошагового добавления урока в Telegram-боте (JSON).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('telegram_state')->nullable()->after('telegram_link_code_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('telegram_state');
        });
    }
};
