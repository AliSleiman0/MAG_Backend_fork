<?php

namespace App\Http\Controllers\APi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\CoursePrerequisite;
use Illuminate\Support\Facades\DB;


class ITController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function set_pos(Request $request)
    {
        $departmentid = $request->departmentId;
        $newCourses = $request->courses;
        $coursesPreCore = $request->prerequisitesCorequisites;
        // Extract all unique course Codes
        $courseCode = [];
        foreach ($coursesPreCore as $course) {
            $courseCode[] = $course['coursecode'];
            if (!empty($course['coursePre'])) {
                $courseCode[] = $course['coursePre'];
            }
            if (!empty($course['courseCo'])) {
                $courseCode[] = $course['courseCo'];
            }
        }
        $coursePreCodes = array_unique($courseCode);
        $newCourses = collect($newCourses);
        $allCoursesCodes = Course::pluck('coursecode');
        $newCoursesCodes = $newCourses->pluck('code');
        $commonCodes = $newCoursesCodes->intersect($allCoursesCodes);
        $filteredCourses = $newCoursesCodes->diff($allCoursesCodes)->values();
        $commonCoursesIDs = Course::whereIn('coursecode', $commonCodes)->get(['courseid']);
        $courseDepartmentCheck = $commonCoursesIDs->map(function ($course) use ($departmentid) {
            return [
                'courseid' => $course['courseid'],
                'departmentid' => $departmentid
            ];
        })->toArray();
        $existingPairs = DB::table('course_department')
            ->select('courseid', 'departmentid')
            ->get()
            ->map(function ($item) {
                return $item->courseid . '-' . $item->departmentid;
            })
            ->toArray();
        $newPairs = collect($courseDepartmentCheck)
            ->map(fn($pair) => $pair['courseid'] . '-' . $pair['departmentid']);

        // Find pairs in newPairs that are not in existingPairs
        $diff = $newPairs->diff($existingPairs);
        $toInsert = $diff->map(fn($key) => [
            'courseid' => explode('-', $key)[0],
            'departmentid' => explode('-', $key)[1]
        ])->toArray();

        // Insert only new pairs
        if (!empty($toInsert)) {
            DB::table('course_department')->insert($toInsert);
        }
        // Assuming $newCourses is a collection of course models
        $filteredCourseInfo = $newCourses->whereIn('code', $filteredCourses);
        $courseData = $filteredCourseInfo->map(function ($course) {
            return [
                'coursecode' => $course['code'],
                'coursename' => $course['title'] . ':' . $course['code'],
                'credits' => $course['credits'],
                'coursetype' => $course['type'],
                'semester' => $course['semesters'],
            ];
        })->toArray();
        $insertion = Course::insert($courseData);
        if ($insertion) {
            $insertedCourseIDCode = Course::whereIn('coursecode', $filteredCourses)->get(['courseid', 'coursecode']);
            $courseDepartment = $insertedCourseIDCode->map(function ($course) use ($departmentid) {
                return [
                    'courseid' => $course['courseid'],
                    'departmentid' => $departmentid
                ];
            })->toArray();
            $insertion = DB::table('course_department')->insert($courseDepartment);
            $coursePreCoIDS = Course::whereIn('coursecode', $coursePreCodes)->get(['courseid', 'coursecode']);
            // Create a mapping of course codes to their IDs
            $codeToIdMap = [];
            foreach ($coursePreCoIDS as $course) {
                $codeToIdMap[$course['coursecode']] = $course['courseid'];
            }

            // Map course codes to their respective IDs
            $finalMappedCourses = [];
            foreach ($coursesPreCore as $course) {
                $courseId = $course['coursecode'];
                $coursePre = $course['coursePre'];
                $courseCo = $course['courseCo'];

                $finalMappedCourses[] = [
                    'courseid' => $codeToIdMap[$courseId] ?? null,
                    'prerequisitecourseid' => $codeToIdMap[$coursePre] ?? null,
                    'corerequisiteid' => $courseCo ? ($codeToIdMap[$courseCo] ?? null) : null
                ];
            }
            $coursePreTable = CoursePrerequisite::get(['courseid', 'prerequisitecourseid', 'corerequisiteid']);
            // Create an index for quick lookup
            $coursePreTableIndex = [];
            foreach ($coursePreTable as $coursePre) {
                // Use courseid as the key, but allow multiple entries
                $courseId = $coursePre['courseid'];
                $coursePreTableIndex[$courseId][] = $coursePre;
            }

            // Check for mismatches
            $mismatchedCourses = [];
            foreach ($finalMappedCourses as $mappedCourse) {
                $courseCode = $mappedCourse['courseid'];

                // If the course is not found in the database, it's a new course
                if (!isset($coursePreTableIndex[$courseCode])) {
                    $mismatchedCourses[] = $mappedCourse;
                } else {
                    // Check each possible match for this courseid
                    $foundMatch = false;
                    foreach ($coursePreTableIndex[$courseCode] as $dbCourse) {
                        $preMatches = ($dbCourse['prerequisitecourseid'] ?? null) === ($mappedCourse['prerequisitecourseid'] ?? null);
                        $coMatches = ($dbCourse['corerequisiteid'] ?? null) === ($mappedCourse['corerequisiteid'] ?? null);

                        if ($preMatches && $coMatches) {
                            $foundMatch = true;
                            break;
                        }
                    }

                    // If no exact match found, add to mismatched
                    if (!$foundMatch) {
                        $mismatchedCourses[] = $mappedCourse;
                    }
                }
            }
        }
        // Insert the mismatched courses
        if (!empty($mismatchedCourses)) {
            $insertion = CoursePrerequisite::insert($mismatchedCourses);
        }

        return [
            'Inserted Successfully?' => $insertion
        ];
    }
}
