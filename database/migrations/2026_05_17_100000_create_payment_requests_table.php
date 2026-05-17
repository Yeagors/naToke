<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()
                ->comment('Who is being topped up (the balance recipient)');
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete()
                ->comment('Admin who initiated; null when user initiated for self');
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('pending')->index();
            $table->string('gateway', 50)->default('fake')->index();
            $table->string('external_id', 255)->nullable()->index()
                ->comment('Provider-side payment id (e.g. T-Bank PaymentId)');
            $table->text('qr_payload')->nullable()
                ->comment('String encoded in the QR (URL or SBP string)');
            $table->string('qr_url')->nullable()
                ->comment('Provider-side direct deeplink for mobile bank app');
            $table->json('gateway_payload')->nullable()
                ->comment('Raw provider response/notification (audit)');
            $table->string('comment', 500)->nullable();
            $table->string('failed_reason', 500)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete()
                ->comment('Resulting deposit transaction once confirmed');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
