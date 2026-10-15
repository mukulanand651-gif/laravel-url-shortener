<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('companies', 'singleton_key')) {
            Schema::table('companies', function (Blueprint $t) {
                $t->dropUnique('companies_singleton_key_unique');
                $t->dropColumn('singleton_key');
            });
        }
    }
    public function down(): void
    {
        if (!Schema::hasColumn('companies', 'singleton_key')) {
            Schema::table('companies', function (Blueprint $t) {
                $t->unsignedTinyInteger('singleton_key')->default(1)->unique();
            });
        }
    }
};
