<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Invitation extends Model {
 protected $fillable=['company_id','invited_by','email','role','token_hash','expires_at','accepted_at'];
 protected function casts(): array { return ['expires_at'=>'datetime','accepted_at'=>'datetime']; }
}
