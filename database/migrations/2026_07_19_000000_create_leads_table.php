<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('source')->default('ai_demo');   // ai_demo | avito | ...
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('model')->nullable();
            $table->string('tariff')->nullable();
            $table->string('visit_at')->nullable();          // свободный текст: «завтра после 18»
            $table->string('result')->default('new');        // booked | handoff | think | reject | new
            $table->string('reason')->nullable();            // причина отказа / хендоффа
            $table->text('summary')->nullable();             // краткая выжимка диалога
            $table->string('ref')->nullable();               // NT-XXXXXX
            $table->timestamps();

            $table->index(['result', 'created_at']);
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
