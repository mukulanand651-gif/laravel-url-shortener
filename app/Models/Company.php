<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Company extends Model
{
    protected $fillable = ['name'];
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
    public function shortUrls(): HasMany
    {
        return $this->hasMany(ShortUrl::class);
    }
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }
}
