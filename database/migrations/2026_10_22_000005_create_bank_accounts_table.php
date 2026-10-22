<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('bank_name');
            $table->enum('account_type', ['checking', 'savings', 'credit_card', 'money_market'])->default('checking');
            $table->string('account_number')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->foreignId('gl_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('last_reconciled')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
