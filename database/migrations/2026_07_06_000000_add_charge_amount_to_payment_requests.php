<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            // amount        = сумма пополнения (зачисляется на баланс)
            // charge_amount = сумма к списанию через СБП (amount + комиссия сервиса)
            $table->decimal('charge_amount', 12, 2)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropColumn('charge_amount');
        });
    }
};
