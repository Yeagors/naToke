<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('model');
            $table->unsignedSmallInteger('year')->nullable();
            $table->decimal('balance', 12, 2)->default(0);
            $table->text('comment')->nullable();
            $table->unsignedInteger('battery_capacity')->nullable()->comment('Wh');
            $table->string('battery_number')->nullable();
            $table->string('license_plate')->unique();
            $table->string('photo')->nullable();
            $table->timestamps();

            $table->index(['brand', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
