<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10);              // income | expense
            $table->decimal('amount', 14, 2);
            $table->string('comment', 500)->nullable();
            $table->string('source', 20)->default('manual'); // manual | sbp
            $table->foreignId('payment_request_id')->nullable()->constrained('payment_requests')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('splits')->nullable();      // снимок долей: [{login,name,percent,amount}]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_transactions');
    }
};
