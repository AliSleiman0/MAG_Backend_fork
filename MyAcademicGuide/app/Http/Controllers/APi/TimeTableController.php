<?php

namespace App\Http\Controllers\APi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Timetable;
use Carbon\Carbon;

class TimeTableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function setschedule(Request $request)
    {
        $courseDetails = $request->courseids; // now this contains courseid along with additional details
        $semester = $request->semester;
        $year = $request->year;
        $campusid = $request->user()->campusid;

        $relative_offerings = [];

        // Extract only the course IDs for querying
        $courseIds = array_map(function ($course) {
            return $course['courseid']; // Extract the courseid for querying the timetable
        }, $courseDetails);

        // Fetch offerings from the timetable
        $offerings = TimeTable::where('semester', $semester)
            ->where('campusid', $campusid)
            ->where('year', $year)
            ->whereIn('courseid', $courseIds)
            ->with('professor')
            ->get();

        // Group offerings by courseid
        $grouped = $offerings->groupBy('courseid')->map(function ($items, $courseid) use ($courseDetails) {
            // Find the course details by matching courseid
            $courseDetail = collect($courseDetails)->firstWhere('courseid', $courseid);

            return [
                'courseid' => (int) $courseid,
                'coursecode' => $courseDetail['coursecode'],
                'coursename' => $courseDetail['coursename'],
                'coursetype' => $courseDetail['coursetype'],
                'credits' => $courseDetail['credits'],
                'sections' => $items->map(function ($item) {
                    $professor = $item->professor->fullname ?? 'Professor Info Not Available'; // fallback if missing
                    $dayMap = [
                        'Mon' => 1,
                        'Tue' => 2,
                        'Wed' => 3,
                        'Thu' => 4,
                        'Fri' => 5,
                        'Sat' => 6,
                        'Sun' => 7,
                    ];

                    // Convert days string to indexed array
                    $dayIndexes = collect(explode(',', $item->days))
                        ->map(function ($day) use ($dayMap) {
                            return $dayMap[trim($day)] ?? null;
                        })
                        ->filter() // remove nulls if any unrecognized day
                        ->values()
                        ->all();
                    return [
                        'id' => $item->timetableid,
                        'daysOfWeek' => $dayIndexes,
                        'days' => $item->days,
                        'startTime' => $item->start_time,
                        'endTime' => $item->end_time,
                        'instructor' => $professor,
                    ];
                })->values()->all(), // Ensures 'sections' is a plain array of objects [{} , {}]
            ];
        })->values();
        return $grouped;
    }
    public function smartschedule(Request $request)
    {
        $primarycourses = $request->input('CourseOfferingsPreferencesIDs');
        $courses = collect($primarycourses); // Ensure primarycourses is a collection
        $breaks = $request->Breaks;

        $validCourses = $courses->map(function ($course) use ($breaks) {
            $course = collect($course); // Convert the course to a collection

            // Normalize start and end times inside sections with Carbon
            $course['sections'] = collect($course['sections'])->map(function ($section) {
                // Parse startTime and endTime to Carbon instances
                $section['startTime'] = Carbon::parse($section['startTime']);
                $section['endTime'] = $section['endTime'] ? Carbon::parse($section['endTime']) : null; // Only parse if endTime exists

                return $section;
            });

            // Filter out sections that overlap with breaks
            $filteredSections = $course['sections']->filter(function ($section) use ($breaks) {
                foreach ($breaks as $break) {
                    // Parse the break's days into an array
                    $sectionDays = explode(',', $section['days']);
                    $breakDays = explode(',', $break['days']);

                    // Check for at least one common day
                    $dayOverlap = false;
                    foreach ($sectionDays as $sectionDay) {
                        if (in_array(trim($sectionDay), $breakDays)) {
                            $dayOverlap = true;
                            break;
                        }
                    }

                    if (!$dayOverlap) {
                        continue; // Skip if no overlapping day
                    }
                    // Parse the break start and end times into Carbon instances
                    $break['start'] = Carbon::parse($break['starttime']);
                    $break['end'] = Carbon::parse($break['endtime']);

                    // Check for overlap with the break
                    if ($this->overlapsWithBreak($section, $break)) {
                        return false; // Overlaps with a break → remove
                    }
                }
                return true; // No overlap → keep
            })->values(); // Re-index the collection after filtering

            // Update the sections in the course object
            $course['sections'] = $filteredSections;

            return $course;
        })->filter(function ($course) {
            return collect($course['sections'])->isNotEmpty(); // Keep only courses with at least one valid section
        })->values(); // Re-index the collection
        $validCourses = $validCourses->map(function ($course) {
            $course['sections'] = collect($course['sections'])->map(function ($section) {
                $section['startTime'] = $section['startTime']->format('H:i');
                $section['endTime'] = $section['endTime'] ? $section['endTime']->format('H:i') : null;
                return $section;
            });
            return $course;
        });

        return $validCourses; // Return or further process the filtered courses
    }

    private function overlapsWithBreak($section, $break)
    {
        return $section['startTime']->lt($break['end']) && $section['endTime']->gt($break['start']);
    }
}
