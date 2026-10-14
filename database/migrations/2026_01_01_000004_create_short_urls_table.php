<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('short_urls', function(Blueprint $t) {
  $t->id(); $t->foreignId('company_id')->constrained()->cascadeOnDelete();
  $t->foreignId('user_id')->constrained()->cascadeOnDelete();
  $t->string('code',12)->unique(); $t->text('original_url');
  $t->unsignedBigInteger('visits')->default(0); $t->timestamps();
 }); }
 public function down(): void { Schema::dropIfExists('short_urls'); }
};
