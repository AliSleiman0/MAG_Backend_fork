<?php

namespace App\Http\Controllers\APi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;

class AutomatedPOSController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function automated_pos(Request $request)
    {
        $user = $request->user()->userid;
        $currentsemester = $this->getcurrentsemester();
        $availablecourses = [];
        $doneCourseIds = [];
        $doneCourses = [];
        $offeredcourses = [];
        $test = [];
        $courses = Student::where('studentid', $user)->with('department.courses.enrollments', 'department.courses.prerequisites')->first();
        foreach ($courses->department->courses as $course) {
            foreach ($course->enrollments as $enrollment) {
                if ($enrollment->status === 'Passed' || $enrollment->status === 'Registered') {
                    $doneCourses[] = $course->courseid;
                    $doneCourses = array_unique($doneCourses);
                    break;
                }
            }
        }
        foreach ($courses->department->courses as $course) {
            if ($course['semester'] == $currentsemester) {
                $availablecourses[] = $course;
            }


            // Step 1: Collect done (Passed or Registered) course IDs
            foreach ($availablecourses as $course) {
                if ($course->enrollments->isNotEmpty()) {
                    foreach ($course->enrollments as $enrollment) {
                        if ($enrollment->status === 'Passed' || $enrollment->status === 'Registered') {
                            $doneCourseIds[] = $course->courseid;
                            $doneCourseIds = array_unique($doneCourseIds);
                            break;
                        }
                    }
                }
            }

            // Step 2: Check each course for eligibility
            foreach ($availablecourses as $course) {
                // Skip already done courses
                if (in_array($course->courseid, $doneCourseIds)) {

                    continue;
                }

                // If no prerequisites, recommend directly
                if ($course->prerequisites->isEmpty()) {
                    $offeredcourses[] = $course;
                    continue;
                }
                // Check if all prerequisites are in $doneCourseIds
                $allPrereqsDone = true;
                foreach ($course->prerequisites as $prereq) {
                    if (!in_array($prereq->prerequisitecourseid, $doneCourses)) {
                        $allPrereqsDone = false;
                        break;
                    }
                }

                if ($allPrereqsDone) {
                    $offeredcourses = $course;
                }
            }
        }
        return $doneCourses;
    }
    private function getcurrentsemester()
    {
        $month = now()->month;
        if ($month > 9 && $month <= 12) {
            return 'Spring';
        } elseif ($month > 1 && $month < 5) {
            return 'Fall';
        } else {
            return 'Summer';
        }
    }
}
