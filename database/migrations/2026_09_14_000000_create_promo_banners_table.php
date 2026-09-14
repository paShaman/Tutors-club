<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promo_banners', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->text('message');
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            // Окно показа: пустые даты означают отсутствие ограничения с этой стороны.
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            // На сколько дней баннер скрывается после того, как пользователь его закрыл.
            $table->unsignedSmallInteger('dismiss_days')->default(7);
            $table->boolean('is_active')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_banners');
    }
};
