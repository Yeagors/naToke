<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained('cars')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tariff_id')->nullable()->constrained('tariffs')->nullOnDelete();

            $table->enum('status', ['open', 'paused', 'closed'])->default('open');

            // Snapshots from tariff at start time (so changes to tariff don't affect active rentals)
            $table->decimal('amount', 12, 2)->comment('amount charged per period (snapshot)');
            $table->enum('period', ['minute', 'hour', 'day', 'week', 'month']);
            $table->unsignedInteger('period_count')->default(1);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->json('extras')->nullable();

            $table->timestamp('started_at');
            $table->timestamp('next_charge_at')->nullable();
            $table->timestamp('last_charged_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->text('comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index(['car_id', 'status']);
            $table->index('next_charge_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
