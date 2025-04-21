<?php

namespace App\Http\Controllers\APi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Timetable;

class TimeTableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function setschedule(Request $request)
    {
        $coursesIds = $request->courseids;
        $semester = $request->semester;
        $year = $request->year;
        $campusid = $request->user()->campusid;
        $relative_offerings = [];
        $offerings = TimeTable::where('semester', $semester)->where('campusid', $campusid)
            ->where('year', $year)->whereIn('courseid', $coursesIds)->with('professor')->get();
        // Group offerings by courseid
        $grouped = $offerings->groupBy('courseid')->map(function ($items, $courseid) {
            return [
                'courseid' => (int) $courseid,
                'offerings' =>  $items->map(function ($item) {
                    $professor = $item->professor->fullname; // fallback if missing
                    return $professor . ' | ' . $item->days . ' | ' . $item->time;
                })->values()
            ];
        })->values();
        return $grouped;
    }
}
