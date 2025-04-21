<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Advisor extends Model
{
    use HasFactory;

    protected $fillable = ['advisorid', 'schoolid'];
    protected $table = 'advisor';
    protected $primaryKey = 'advisorid'; // Tell Laravel the correct primary key
    public function user()
    {
        return $this->belongsTo(User::class, 'advisorid', 'userid');
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'schoolid', 'schoolid');
    }
}
