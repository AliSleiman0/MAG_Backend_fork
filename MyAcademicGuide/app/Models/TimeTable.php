<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    use HasFactory;

    protected $fillable = ['userid', 'courseid', 'semester', 'year', 'days', 'time', 'campus'];
    protected $table = 'time_table';
    protected $primaryKey = 'timetableid';
    public function course()
    {
        return $this->belongsTo(Course::class, 'courseid', 'courseid');
    }

    public function professor()
    {
        return $this->belongsTo(User::class, 'userid', 'userid');
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campusid', 'campusid');
    }
}
