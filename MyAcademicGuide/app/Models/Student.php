<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;
    protected $table = 'student';
    protected $fillable = ['studentid', 'major', 'enrollmentyear', 'departmentid'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'studentid', 'userid');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'departmentid', 'departmentid');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'studentid', 'studentid');
    }
}
