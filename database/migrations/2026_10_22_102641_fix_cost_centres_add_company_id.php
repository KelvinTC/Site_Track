<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if company_id column exists
        if (!Schema::hasColumn('cost_centres', 'company_id')) {
            Schema::table('cost_centres', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        // Drop the old unique constraint on code
        Schema::table('cost_centres', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        // Add new composite unique constraint (company_id, code)
        Schema::table('cost_centres', function (Blueprint $table) {
            $table->unique(['company_id', 'code']);
        });

        // Assign existing cost centres to first company if company_id is null
        $firstCompanyId = DB::table('companies')->first()?->id;
        if ($firstCompanyId) {
            DB::table('cost_centres')->whereNull('company_id')->update(['company_id' => $firstCompanyId]);
        }
    }

    public function down(): void
    {
        Schema::table('cost_centres', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->unique('code');
        });
    }
};
