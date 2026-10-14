<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class DatabaseSeeder extends Seeder {
 public function run(): void {
  // Prepared RAW SQL as required. A supplied secret is required for real environments.
  $email=env('SEED_SUPERADMIN_EMAIL','superadmin@example.test');
  $password=env('SEED_SUPERADMIN_PASSWORD');
  if (!$password) { throw new \RuntimeException('Set SEED_SUPERADMIN_PASSWORD before seeding.'); }
  $sql = DB::getDriverName() === 'mysql'
   ? 'INSERT IGNORE INTO users (name,email,password,role,company_id,created_at,updated_at) VALUES (?,?,?,?,NULL,?,?)'
   : 'INSERT INTO users (name,email,password,role,company_id,created_at,updated_at) VALUES (?,?,?,?,NULL,?,?) ON CONFLICT(email) DO NOTHING';
  DB::insert($sql,['Super Admin',$email,Hash::make($password),'SuperAdmin',now(),now()]);
 }
}
