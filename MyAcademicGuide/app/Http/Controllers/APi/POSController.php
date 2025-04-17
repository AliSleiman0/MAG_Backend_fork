<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class POSController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function pos(Request $request)
    {
        $user = $request->user()->userid;
        $courses = Student::where('studentid', $user)->with('department.courses.enrollments.timetable', 'department.courses.prerequisites', 'department.courses.prerequisiteFor', 'department.courses.prerequisites')->first();
        $passedcourses = [];
        $registeredcourses = [];
        $remainingcourses = [];
        $canregister = [];
        $cannotregister = [];
        $allCoursesInfo = $courses->department->courses;
        $CoursesIds = $allCoursesInfo->pluck('courseid')->unique()->toArray();
        // Loop through the courses under the department
        foreach ($courses->department->courses as $course) {
            $coursestatus = Null;
            if ($course->enrollments && $course->enrollments->isNotEmpty()) { // Check if the course has enrollments
                foreach ($course->enrollments as $enrollment) {
                    $course->status = $enrollment->status;
                    if ($enrollment->status == 'Passed') {
                        $course->status = $enrollment->status;
                        $course->semestertaken = $enrollment->timetable->semester;
                        $course->yeartaken = $enrollment->timetable->year;
                        $passedcourses[] = $this->prepareCourseData($course, $CoursesIds); // Add the course to the passedcourses array
                        $coursestatus = 'Passed';
                        break; // Stop checking further enrollments for this course
                    } elseif ($enrollment->status == 'Registered') {
                        $coursestatus = 'Registered';
                        $registeredcourses[] = $this->prepareCourseData($course, $CoursesIds);
                        break;
                    }
                }
                if ($coursestatus == Null) {
                    $remainingcourses[] = $this->prepareCourseData($course, $CoursesIds);
                }
            } else {
                $remainingcourses[] = $this->prepareCourseData($course, $CoursesIds);
            }
        }

        foreach ($remainingcourses as $course) {
            if ($course['prerequisites']) { // Check if the course has prerequisites
                $prerequisites = $course['prerequisites']; // Fetch prerequisite courses
                $allPrerequisitesMet = true;

                foreach ($prerequisites as $prerequisite) {
                    // Skip if the prerequisite course is not part of the student's major
                    if (!in_array($prerequisite['prerequisitecourseid'], $CoursesIds)) {
                        continue;
                    }
                    $isInPassed = collect($passedcourses)->contains('courseid', $prerequisite['prerequisitecourseid']);
                    $isInRegistered = collect($registeredcourses)->contains('courseid', $prerequisite['prerequisitecourseid']);

                    // If a prerequisite is neither passed nor registered, mark course as cannot register
                    if (!$isInPassed && !$isInRegistered) {
                        $allPrerequisitesMet = false;
                        break; // Stop checking further prerequisites for this course
                    }
                }

                if ($allPrerequisitesMet) {
                    $canregister[] = $course;
                } else {
                    $cannotregister[] = $course;
                }
            } else {
                $canregister[] = $course;
            }
        }
        $allCourses = $courses->department->courses->map(function ($course) use ($cannotregister, $canregister, $CoursesIds) {
            return $this->prepareAllCourseData($course, $canregister, $cannotregister, $CoursesIds);
        });


        return response()->json([
            'All Courses' => $allCourses,
            'Passed' => $passedcourses,
            'Currently Registered' => $registeredcourses,
            'Remaining Courses' => $remainingcourses,
            'Can Register' => $canregister,
            'Cannot Register' => $cannotregister,
        ]);
    }

    /**
     * Prepare course data for response
     */
    private function prepareAllCourseData($course, $canregister = [], $cannotregister = [], $CoursesIds)
    {
        $prerequisit = [];
        $postrequisit = [];
        $corerequisites = [];
        foreach ($course->prerequisites as $prerequisiteId) {
            if ($prerequisiteId->prerequisitecourseid != null) {
                if (!in_array($prerequisiteId['prerequisitecourseid'], $CoursesIds)) {
                    continue;
                }
                $prerequisit[] = $prerequisiteId;
            }
        }
        foreach ($course->prerequisiteFor as $prerequisite) {
            if ($prerequisite->courseid != null) {
                if (!in_array($prerequisite['corerequisiteid'], $CoursesIds)) {
                    continue;
                }
                $postrequisit[] = $prerequisite->courseid;
            }
        }
        foreach ($course->corerequisites as $core) {
            if ($core->corerequisiteid != null) {
                if (!in_array($core['corerequisiteid'], $CoursesIds)) {
                    continue;
                }
                $corerequisites[] = $core->corerequisiteid;
            }
        }

        // Check if course exists in canregister or cannotregister and set status
        if (collect($canregister)->contains('courseid', $course->courseid)) {
            $status = 'Can Register';
        } elseif (collect($cannotregister)->contains('courseid', $course->courseid)) {
            $status = 'Cannot Register';
        } else {
            $status = $course->status; // Keep original status if not in lists
        }

        return [
            'courseid' => $course->courseid,
            'coursecode' => $course->coursecode,
            'coursename' => $course->coursename,
            'credits' => $course->credits,
            'semester' => $course->semester,
            'coursetype' => $course->coursetype,
            'status' => $status, // Updated status
            'prerequisites' => $prerequisit,
            'postrequisites' => $postrequisit,
            'corerequisites' => $corerequisites
        ];
    }


    private function prepareCourseData($course, $CoursesIds)
    {
        $prerequisit = [];
        $postrequisit = [];
        $corerequisites = [];
        foreach ($course->prerequisites as $prerequisiteId) {
            if ($prerequisiteId->prerequisitecourseid != null) {
                if (!in_array($prerequisiteId['prerequisitecourseid'], $CoursesIds)) {
                    continue;
                }
                $prerequisit[] = $prerequisiteId;
            }
        }
        foreach ($course->prerequisiteFor as $prerequisite) {
            if ($prerequisite->courseid != null) {
                if (!in_array($prerequisite['corerequisiteid'], $CoursesIds)) {
                    continue;
                }
                $postrequisit[] = $prerequisite->courseid;
            }
        }
        foreach ($course->corerequisites as $core) {
            if ($core->corerequisiteid != null) {
                if (!in_array($core['corerequisiteid'], $CoursesIds)) {
                    continue;
                }
                $corerequisites[] = $core->corerequisiteid;
            }
        }
        if ($course->status == 'Passed') {
            return [
                'courseid' => $course->courseid,
                'coursecode' => $course->coursecode,
                'coursename' => $course->coursename,
                'credits' => $course->credits,
                'semester' => $course->semester,
                'coursetype' => $course->coursetype,
                'semestertaken' => $course->semestertaken,
                'yeartaken' => $course->yeartaken,
                'prerequisites' => $prerequisit,
                'postrequisites' => $postrequisit,
                'corerequisites' => $corerequisites

            ];
        } else {
            return [
                'courseid' => $course->courseid,
                'coursecode' => $course->coursecode,
                'coursename' => $course->coursename,
                'credits' => $course->credits,
                'semester' => $course->semester,
                'coursetype' => $course->coursetype,
                'prerequisites' => $prerequisit,
                'postrequisites' => $postrequisit,
                'corerequisites' => $corerequisites

            ];
        }
    }
}
