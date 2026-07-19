<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('battery_rental', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('rentals')->cascadeOnDelete();
            $table->foreignId('battery_id')->constrained('batteries')->cascadeOnDelete();
            $table->unique(['rental_id', 'battery_id']);
        });

        // Перенос текущей одиночной привязки в pivot.
        foreach (DB::table('rentals')->whereNotNull('battery_id')->get(['id', 'battery_id']) as $r) {
            DB::table('battery_rental')->insertOrIgnore(['rental_id' => $r->id, 'battery_id' => $r->battery_id]);
        }

        Schema::table('rentals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('battery_id');
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->foreignId('battery_id')->nullable()->after('tariff_id')
                ->constrained('batteries')->nullOnDelete();
        });
        // Вернём первую АКБ каждой аренды в одиночную колонку.
        foreach (DB::table('battery_rental')->orderBy('id')->get() as $row) {
            DB::table('rentals')->where('id', $row->rental_id)->whereNull('battery_id')
                ->update(['battery_id' => $row->battery_id]);
        }
        Schema::dropIfExists('battery_rental');
    }
};
