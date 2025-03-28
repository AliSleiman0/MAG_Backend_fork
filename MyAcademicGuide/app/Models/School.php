<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;
    protected $table = 'school';
    protected $fillable = ['schoolname'];

    public function advisors(): HasMany
    {
        return $this->hasMany(Advisor::class, 'schoolid', 'schoolid');
    }
}
