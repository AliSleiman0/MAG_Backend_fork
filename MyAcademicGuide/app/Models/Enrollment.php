<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;
    protected $table = 'enrollment';
    protected $primaryKey = 'enrollmentid'; // Tell Laravel the correct primary key
    protected $fillable = ['studentid', 'courseid', 'timetableid', 'status'];

    public function student()
    {
        return $this->belongsTo(Student::class, 'studentid', 'studentid');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'courseid', 'courseid');
    }

    public function timetable()
    {
        return $this->belongsTo(Timetable::class, 'timetableid', 'timetableid');
    }
}
