<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number')->unique();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('site_job_id')->nullable()->constrained('site_jobs')->nullOnDelete();
            $table->date('expense_date');
            $table->string('description');
            $table->string('reference')->nullable(); // Invoice/receipt number
            $table->string('vendor')->nullable(); // Supplier/payee name
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'check', 'credit_card', 'mobile_money'])->nullable();
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->string('receipt_path')->nullable(); // For file attachments
            $table->timestamps();
            $table->softDeletes();

            $table->index('expense_number');
            $table->index('expense_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
