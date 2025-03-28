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
        // Get student with enrollments and related courses (eager load enrollments and courses)
        $completed_courses = Student::where('studentid', $user->userid)
            ->with('enrollments.course')->with(['department' => function ($query) {
                $query->withSum('courses', 'credits')->withCount('courses'); // Count courses at the department level
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
                    return  $enrollment->course; // Get the related course for each enrollment

                })
            ];
        });
        // Return the result with courses depending on the status and their count
        return response()->json([
            'courses_destribution_by_status' => $enrollmentsGrouped,
            'total_courses' => $completed_courses->department->courses_count,
            'total_credits' => $completed_courses->department->courses_sum_credits,

        ]);
    }
}
