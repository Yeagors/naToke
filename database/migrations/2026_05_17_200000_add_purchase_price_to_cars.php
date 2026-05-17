<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            if (! Schema::hasColumn('cars', 'purchase_price')) {
                $table->decimal('purchase_price', 12, 2)->nullable()->after('balance')
                    ->comment('Initial purchase cost — used in unit economics to compute ROI / payback');
                $table->date('purchase_date')->nullable()->after('purchase_price')
                    ->comment('When the car was acquired — defaults to created_at if absent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn(['purchase_price', 'purchase_date']);
        });
    }
};
