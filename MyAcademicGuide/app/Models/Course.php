<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;
    protected $table = 'course';
    protected $primaryKey = 'courseid'; // Tell Laravel the correct primary key
    protected $fillable = ['coursename', 'credits',  'semester'];

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'course_department', 'courseid', 'departmentid');
    }
    // A course can have multiple prerequisites
    public function prerequisites()
    {
        return $this->hasMany(CoursePrerequisite::class, 'courseid', 'courseid');
    }
    // A course can be a prerequisite for multiple courses
    public function prerequisiteFor()
    {
        return $this->hasMany(CoursePrerequisite::class, 'prerequisitecourseid', 'courseid');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'enrollmentid', 'enrollmentid');
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class, 'timetableid', 'timetableid');
    }
}
