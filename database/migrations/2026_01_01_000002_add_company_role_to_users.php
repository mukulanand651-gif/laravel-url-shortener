<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('users', function(Blueprint $t) {
  $t->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
  $t->string('role')->default('Member');
 }); }
 public function down(): void { Schema::table('users', fn(Blueprint $t) => $t->dropConstrainedForeignId('company_id')); Schema::table('users', fn(Blueprint $t) => $t->dropColumn('role')); }
};
