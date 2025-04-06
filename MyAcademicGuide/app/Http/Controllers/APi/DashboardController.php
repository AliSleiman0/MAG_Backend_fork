<?php

namespace App\Http\Controllers\APi;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use \Illuminate\Support\Facades\Facade;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user(); // Get the authenticated user (student)
        $remainingcourses = [];
        // Get student with enrollments and related courses (eager load enrollments and courses)
        $completed_courses = Student::where('studentid', $user->userid)
            ->with('enrollments.course')->with('enrollments.timetable')->with(['department' => function ($query) {
                $query->withSum('courses', 'credits')->withCount('courses')->with('courses.prerequisites'); // Count courses at the department level
            }]) // Eager load the enrollments and related courses
            ->first();

        // Group the enrollments by status and filter only the necessary ones (like "Passed", "Registered")
        $enrollmentsGrouped = $completed_courses->enrollments->groupBy('status')->map(function ($group) use ($completed_courses) {
            $credits_sum = $group->sum(function ($enrollment) {
                return $enrollment->course ? $enrollment->course->credits : 0; // Sum course credits
            });
            return [
                'course_count' => $group->count(), // Count enrollments for each status
                'course_percentage' => round(($group->count() / $completed_courses->department->courses_count) * 100, 2),
                'credits_count' => $credits_sum,
                'credits_percentage' => round(($credits_sum / $completed_courses->department->courses_sum_credits) * 100, 2),
                'courses' => $group->map(function ($enrollment) {
                    $enrollment->course->semestertaken = $enrollment->timetable->semester;
                    $enrollment->course->yeartaken = $enrollment->timetable->year;
                    $enrollment->course->grade = $enrollment->grade;
                    return $enrollment->course; // Get the related course for each enrollment

                })
            ];
        });
        foreach ($completed_courses->department->courses as $course) {
            $isInPassed = collect($enrollmentsGrouped['Passed']['courses'])->contains(function ($passedCourse) use ($course) {
                return isset($passedCourse) && $passedCourse['courseid'] === $course->courseid;
            });

            $isInRegistered = collect($enrollmentsGrouped['Registered']['courses'])->contains(function ($registeredCourse) use ($course) {
                return  isset($registeredCourse) && $registeredCourse['courseid'] === $course->courseid;
            });
            if (!$isInPassed && !$isInRegistered) {
                $remainingcourses[] = $course;
                $course->canregister = false; // Initialize canregister as null
            }
        }

        // Check prerequisites for remaining courses
        foreach ($remainingcourses as $course) {
            $allPrerequisitesMet = true;

            if (!empty($course->prerequisites)) { // Check if the course has prerequisites
                foreach ($course->prerequisites as $prerequisite) {
                    $prerequisiteCourseId = $prerequisite['prerequisitecourseid'];
                    $isInPassed = collect($enrollmentsGrouped['Passed']['courses'])->contains(function ($passedCourse) use ($prerequisiteCourseId) {
                        return isset($passedCourse) && $passedCourse['courseid'] === $prerequisiteCourseId;
                    });

                    $isInRegistered = collect($enrollmentsGrouped['Registered']['courses'])->contains(function ($registeredCourse) use ($prerequisiteCourseId) {
                        return  isset($registeredCourse) && $registeredCourse['courseid'] === $prerequisiteCourseId;
                    });

                    // If a prerequisite is neither passed nor registered, mark course as cannot register
                    if (!$isInPassed && !$isInRegistered) {
                        $allPrerequisitesMet = false;
                        $simpleCourse = [
                            'courseid' => $course->courseid,
                            'coursename' => $course->coursename,
                            'credits' => $course->credits,
                            'semester' => $course->semester,
                            'coursetype' => $course->coursetype,
                            'canregister' => false, // Initialize as null
                        ];
                        $remainedcourses[] = $simpleCourse;
                        break; // Stop checking further prerequisites for this course
                    }
                }
            }

            if ($allPrerequisitesMet) {
                $course->canregister = true; // Mark as can register
                $simpleCourse = [
                    'courseid' => $course->courseid,
                    'coursename' => $course->coursename,
                    'credits' => $course->credits,
                    'semester' => $course->semester,
                    'coursetype' => $course->coursetype,
                    'canregister' => true, // Initialize as null
                ];
                $remainedcourses[] = $simpleCourse;
            }
        }
        $grades = $completed_courses->enrollments
            ->groupBy('grade')
            ->mapWithKeys(function ($group, $grade) {
                return [$grade => $group->count()];
            });

        // Return the result with courses depending on the status and their count
        return response()->json([
            'courses_destribution_by_status' => $enrollmentsGrouped,
            'remaining_courses' => $remainedcourses,
            'grades_distribution' => $grades,
            'total_courses' => $completed_courses->department->courses_count,
            'total_credits' => $completed_courses->department->courses_sum_credits,

        ]);
    }
}
