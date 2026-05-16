<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            if (! Schema::hasColumn('tariffs', 'is_buyout')) {
                $table->boolean('is_buyout')->default(false)->after('extras')
                    ->comment('Lease-to-own (раскат) mode. When true, buyout_price and buyout_days apply.');
                $table->decimal('buyout_price', 12, 2)->nullable()->after('is_buyout')
                    ->comment('Full price the renter must pay to own the car.');
                $table->unsignedInteger('buyout_days')->nullable()->after('buyout_price')
                    ->comment('Number of payment periods until full ownership.');
            }
        });

        Schema::table('rentals', function (Blueprint $table) {
            if (! Schema::hasColumn('rentals', 'is_buyout')) {
                $table->boolean('is_buyout')->default(false)->after('extras');
                $table->decimal('buyout_price', 12, 2)->nullable()->after('is_buyout')
                    ->comment('Snapshot of tariff.buyout_price at rental creation.');
                $table->unsignedInteger('buyout_days_total')->nullable()->after('buyout_price')
                    ->comment('Snapshot of tariff.buyout_days at rental creation.');
                $table->decimal('buyout_remaining', 12, 2)->nullable()->after('buyout_days_total')
                    ->comment('Remaining buyout amount; decreases on each charge.');
                $table->unsignedInteger('buyout_days_remaining')->nullable()->after('buyout_remaining')
                    ->comment('Remaining payment periods; decreases on each charge.');
                $table->timestamp('buyout_completed_at')->nullable()->after('buyout_days_remaining')
                    ->comment('Set when buyout_remaining reaches zero.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn([
                'is_buyout', 'buyout_price', 'buyout_days_total',
                'buyout_remaining', 'buyout_days_remaining', 'buyout_completed_at',
            ]);
        });
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn(['is_buyout', 'buyout_price', 'buyout_days']);
        });
    }
};
