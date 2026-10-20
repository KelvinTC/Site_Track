<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add VAT fields to company_profiles
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->string('vat_number', 20)->nullable()->after('tax_id');
            $table->decimal('vat_rate', 5, 2)->default(15.00)->after('vat_number');
            $table->boolean('vat_registered')->default(false)->after('vat_rate');
        });

        // Add VAT number to customers
        Schema::table('customers', function (Blueprint $table) {
            $table->string('vat_number', 20)->nullable()->after('zip');
        });

        // Add VAT breakdown fields to invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->default(15.00)->after('subtotal');
            $table->decimal('vat_amount', 12, 2)->default(0)->after('vat_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn(['vat_number', 'vat_rate', 'vat_registered']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('vat_number');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['vat_rate', 'vat_amount']);
        });
    }
};