<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default departments
        DB::table('departments')->insert([
            ['name' => 'Finance', 'description' => 'Finance Department', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Transport', 'description' => 'Transport Department', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Executive', 'description' => 'Executive Department', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'General Staff', 'description' => 'General Staff Department', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Casual', 'description' => 'Casual Workers', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};