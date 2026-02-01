<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE invoices ALTER COLUMN due_date DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE invoices ALTER COLUMN due_date SET NOT NULL');
    }
};
