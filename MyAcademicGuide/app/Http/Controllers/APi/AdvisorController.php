<?php

namespace App\Http\Controllers\APi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Str;

class AdvisorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getstudents(Request $request, User $user)
    {
        $queryString = $request->search ?? ''; // Safe retrieval of search term
        $queryfilter = $request->filter ?? '';
        $finalform = [];
        $advisors = User::where('userid', $user->userid)->where('usertype', 'Advisor')->with('advisor')->get(['userid', 'campusid']);
        $advisorschool = $advisors[0]->advisor->schoolid;
        $students = User::where('usertype', 'Student')->where('campusid', $advisors[0]->campusid)->with(['student.department', 'campus'])->whereHas('student.department', function ($query) use ($advisorschool, $queryfilter) {
            $query->where('schoolid', $advisorschool);
            if ($queryfilter) {
                $query->where('departmentid', $queryfilter);
            }
        })->when($queryString, function ($query) use ($queryString) {
            $query->where(function ($q) use ($queryString) {
                $q->where('fullname', 'LIKE', "%{$queryString}%")
                    ->orWhere('userid', 'LIKE', "%{$queryString}%");
            });
        })->get(['userid', 'fullname', 'email', 'image', 'campusid']);
        $finalform = $students->map(function ($students) {
            // Get student with enrollments and related courses (eager load enrollments and courses)
            $totalCredits = Student::where('studentid', $students->userid)
                ->with(['department' => function ($query) {
                    $query->withSum('courses', 'credits')->with('courses'); // Count courses at the department level
                }]) // Eager load the enrollments and related courses
                ->first();
            $studentWithCredits = Student::where('studentid', $students->userid) // Note: using $student, not $students
                ->with(['enrollments' => function ($query) {
                    $query->whereIn('status', ['Passed', 'Registered'])
                        ->with('course'); // Load the course relationship
                }])
                ->first();
            $registeredCourses = [];
            $coursesdone = [];
            foreach ($studentWithCredits->enrollments as $enrollment) {
                $coursesdone[] = $enrollment->course;
                if ($enrollment->status === 'Registered') {
                    $registeredCourses[] = [
                        'courseid' => $enrollment->course->courseid,
                        'coursename' => $enrollment->course->coursename,
                        'credits' => $enrollment->course->credits,
                        // Add any other course details you need
                    ];
                }
            }
            // Then calculate the sum manually
            $creditsFinished = $studentWithCredits->enrollments
                ->sum(function ($enrollment) {
                    return $enrollment->course->credits ?? 0;
                });
            $allcourses = $studentWithCredits->department->courses->toArray();
            $coursesdone = collect($coursesdone)->pluck('courseid')->toArray();

            $filteredCourses = collect($allcourses)
                ->whereNotIn('courseid', $coursesdone)
                ->values()->toArray(); // Reset array keys
            $formattedCourses = array_map(function ($course) {
                return [
                    'courseid' => $course['courseid'],
                    'coursename' => $course['coursename'],
                    'credits' => $course['credits'],
                ];
            }, $filteredCourses);
            return [
                'userid' => $students->userid,
                'fullname' => $students->fullname,
                'email' => $students->email,
                'image' => $students->image,
                'departmentid' => $students->student->department->departmentid,
                'departmentname' => $students->student->department->departmentname,
                'campusname' => $students->campus->campusname,
                'creditsFinished' => $creditsFinished,
                'totalCredits' => $totalCredits->department->courses_sum_credits,
                'currentlyRegisteredCourses' => $registeredCourses,
                'remainingCourses' => $formattedCourses
            ];
        });
        return $finalform;
    }

    public function advisorprofile(Request $request, User $user)
    {
        $profileinfo = User::with(['campus', 'advisor.school'])->where('userid', $user->userid)->first();
        return response()->json([
            'userid' => $profileinfo->userid,
            'fullname' => $profileinfo->fullname,
            'email' => $profileinfo->email,
            'campusname' => $profileinfo->campus->campusname,
            'schoolname' => $profileinfo->advisor->school->schoolname,
            'image' => $profileinfo->image,
        ]);
    }
    public function profile(Request $request, User $user)
    {
        $profileinfo = User::with(['campus'])->where('userid', $user->userid)->first();
        return response()->json([
            'userid' => $profileinfo->userid,
            'usertype' => $profileinfo->usertype,
            'fullname' => $profileinfo->fullname,
            'email' => $profileinfo->email,
            'campusname' => $profileinfo->campus->campusname,
            'image' => $profileinfo->image,
        ]);
    }
}
