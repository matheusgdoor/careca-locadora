<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table): void {
            $table->string('rental_mode', 20)->default('daily')->after('status');
            $table->unsignedInteger('contract_version')->default(1)->after('rental_mode');
            $table->unsignedTinyInteger('billing_day')->nullable()->after('ends_at');
            $table->decimal('included_distance', 12, 2)->nullable()->after('billing_day');
            $table->decimal('extra_distance_value', 15, 4)->default(0)->after('included_distance');
            $table->boolean('protection_included')->default(false)->after('extra_distance_value');
            $table->decimal('protection_deductible', 15, 2)->default(0)->after('protection_included');
            $table->string('fuel_policy', 40)->default('same_level')->after('protection_deductible');

            $table->index(
                ['organization_id', 'rental_mode', 'status'],
                'rental_contracts_org_mode_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table): void {
            $table->dropIndex('rental_contracts_org_mode_status_idx');
            $table->dropColumn([
                'rental_mode',
                'contract_version',
                'billing_day',
                'included_distance',
                'extra_distance_value',
                'protection_included',
                'protection_deductible',
                'fuel_policy',
            ]);
        });
    }
};
