<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;
    protected $table = 'department';
    protected $primaryKey = 'departmentid'; // Tell Laravel the correct primary key
    protected $fillable = ['departmentname', 'schoolid'];

    public function school()
    {
        return $this->belongsTo(School::class, 'schoolid', 'schoolid');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_department', 'departmentid', 'courseid');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'departmentid', 'departmentid');
    }
}
