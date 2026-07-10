<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batteries', function (Blueprint $table) {
            $table->id();
            $table->string('car_model');            // для какой модели (совместимость)
            $table->string('capacity', 50)->nullable(); // ёмкость, напр. "60/45"
            $table->string('vin')->unique();        // вин-номер батареи (уникальный)
            $table->string('callsign')->nullable(); // позывной (из реестра), опционально
            $table->string('comment', 500)->nullable();
            $table->timestamps();
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->foreignId('battery_id')->nullable()->after('tariff_id')
                ->constrained('batteries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('battery_id');
        });
        Schema::dropIfExists('batteries');
    }
};
