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
        Schema::create('casual_site_job', function (Blueprint $table) {
            $table->id();
            $table->foreignId('casual_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_job_id')->constrained()->onDelete('cascade');
            $table->date('assigned_date')->nullable();
            $table->date('work_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['casual_id', 'site_job_id', 'work_date'], 'casual_job_date_unique');
            $table->index('work_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('casual_site_job');
    }
};
