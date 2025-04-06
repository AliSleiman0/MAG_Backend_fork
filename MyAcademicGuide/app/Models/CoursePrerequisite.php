<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoursePrerequisite extends Model
{
    use HasFactory;
    protected $table = 'course_prerequisite';
    protected $primaryKey = 'courseid';
    protected $fillable = ['courseid', 'prerequisitecourseid'];

    // The main course that has prerequisites
    public function course()
    {
        return $this->belongsTo(Course::class, 'courseid', 'courseid');
    }

    // The prerequisite course itself
    public function prerequisiteCourse()
    {
        return $this->belongsTo(Course::class, 'prerequisitecourseid', 'courseid');
    }
}
