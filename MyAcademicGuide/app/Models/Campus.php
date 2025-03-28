<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campus extends Model
{
    use HasFactory;
    protected $fillable = ['campusname'];
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'campusid', 'campusid');
    }
}
