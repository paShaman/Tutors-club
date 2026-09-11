<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->enum('gender', ['boy', 'girl'])->default('boy')->after('name');
            $table->string('color', 32)->nullable()->after('gender');
        });

        // Раздаём цвет уже существующим ученикам, чтобы у всех была своя аватарка.
        $colors = [
            'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal',
            'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink',
            'rose', 'slate', 'gray', 'zinc', 'neutral', 'stone',
        ];
        $ids = DB::table('students')->orderBy('id')->pluck('id');

        foreach ($ids as $index => $id) {
            DB::table('students')
                ->where('id', $id)
                ->update(['color' => $colors[$index % count($colors)]]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['gender', 'color']);
        });
    }
};
