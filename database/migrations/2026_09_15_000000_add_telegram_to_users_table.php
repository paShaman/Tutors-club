<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Привязка Telegram к профилю: chat_id, код одноразовой ссылки и дата связывания.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_chat_id', 32)->nullable()->unique()->after('locale');
            $table->string('telegram_username', 64)->nullable()->after('telegram_chat_id');
            $table->timestamp('telegram_linked_at')->nullable()->after('telegram_username');
            $table->string('telegram_link_code', 64)->nullable()->index()->after('telegram_linked_at');
            $table->timestamp('telegram_link_code_expires_at')->nullable()->after('telegram_link_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['telegram_chat_id']);
            $table->dropColumn([
                'telegram_chat_id',
                'telegram_username',
                'telegram_linked_at',
                'telegram_link_code',
                'telegram_link_code_expires_at',
            ]);
        });
    }
};
