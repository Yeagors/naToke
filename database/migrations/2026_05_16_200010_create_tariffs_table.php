<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->enum('period', ['minute', 'hour', 'day', 'week', 'month'])->default('day');
            $table->unsignedInteger('period_count')->default(1);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->json('extras')->nullable()->comment('[{"label":"страховка","amount":5}]');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariffs');
    }
};
