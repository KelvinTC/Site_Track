<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('zip', 20)->nullable();
            $table->string('country', 100)->default('Kenya');
            $table->string('tax_id', 50)->nullable();
            $table->string('vat_number', 50)->nullable();
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->boolean('vat_registered')->default(false);
            $table->string('logo_path')->nullable();
            $table->string('invoice_prefix', 10)->default('INV');
            $table->string('job_prefix', 10)->default('JOB');
            $table->string('employee_prefix', 10)->default('EMP');
            $table->string('expense_prefix', 10)->default('EXP');
            $table->text('invoice_notes')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
