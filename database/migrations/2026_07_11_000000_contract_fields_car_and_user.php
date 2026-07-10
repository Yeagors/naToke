<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            // Ёмкость аккумулятора теперь строка (напр. "60/45"), а не целое число Вт·ч.
            $table->string('battery_capacity', 50)->nullable()->comment('напр. 60/45')->change();
            $table->string('frame_number')->nullable()->after('battery_number');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('birth_place')->nullable()->after('birth_date');
            $table->string('address_registration')->nullable()->after('birth_place');
            $table->string('address_residence')->nullable()->after('address_registration');
            $table->string('phone2')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('frame_number');
            $table->unsignedInteger('battery_capacity')->nullable()->comment('Wh')->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['birth_place', 'address_registration', 'address_residence', 'phone2']);
        });
    }
};
