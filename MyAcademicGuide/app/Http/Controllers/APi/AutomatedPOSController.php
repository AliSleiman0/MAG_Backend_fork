<?php

namespace App\Http\Controllers\APi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Str;

class AutomatedPOSController extends Controller
{

    public function automated_pos(Request $request)
    {
        $user = $request->user()->userid;
        $currentsemester = $this->getcurrentsemester();
        $majorcount = 0;
        $corecount = 0;
        $gelectivecount = 0;
        $melectivecount = 0;
        $geducationcount = 0;
        $doneCoursesCollect = collect(); // Collection of done courses
        $offeredcourses = [];
        $toberegistered = [];
        // Load student with department, courses, enrollments, prerequisites
        $student = Student::where('studentid', $user)
            ->with('department.courses.enrollments', 'department.courses.prerequisites', 'department.courses.prerequisiteFor', 'enrollments.course', 'department.courses.corerequisites')
            ->first();
        $completedCredits = $student->enrollments
            ->whereIn('status', ['Passed', 'Registered'])
            ->sum(fn($enrollment) => $enrollment->course->credits);
        $total_credit_department = $student->department->courses->sum(fn($course) => $course->credits);
        $remaining_credits = $total_credit_department - $completedCredits;
        $recommended_max_credits = 20;
        // $remaining_semesters_mention = $this->estimateRemainingSemesters($remaining_credits, $currentsemester);
        //$remaining_semesters = count($remaining_semesters_mention);
        // Get all courses in the student's department
        $allCourses = $student->department->courses;
        $CoursesIds = $allCourses->pluck('courseid')->unique()->toArray();
        // Step 1: Identify completed courses
        foreach ($allCourses as $course) {
            foreach ($course->enrollments as $enrollment) {
                if ($enrollment->status === 'Passed' || $enrollment->status === 'Registered') {
                    $doneCoursesCollect->push($course);
                    break;
                }
            }
        }

        $doneCourseIds = $doneCoursesCollect->pluck('courseid')->unique()->toArray();
        $semester_sequence = ['Spring', 'Summer', 'Fall'];
        $current = $this->getCurrentSemester(); // returns ['semester' => 'Spring', 'year' => 2025]
        $current_semester_index = array_search($current['semester'], $semester_sequence);
        $current_year = $current['year'];
        $semester_index = 0;
        // Find the index of the current semester in the sequence
        $remaining_untaken_courses = array_diff($CoursesIds, $doneCourseIds);
        $remaining_semesters = 0;
        while (!empty($remaining_untaken_courses)) {
            $semester = $semester_sequence[($current_semester_index + $semester_index) % count($semester_sequence)];
            $year_offset = intdiv(($current_semester_index + $semester_index), count($semester_sequence));
            $calendar_year = $current_year + $year_offset;

            // Adjust academic year based on semester
            if ($semester === 'Fall') {
                $academic_year = $calendar_year . '-' . ($calendar_year + 1);
            } else {
                $academic_year = ($calendar_year - 1) . '-' . $calendar_year;
            }

            $semester_index++;
            $remaining_semesters++;
            // Adjust max credits based on semester
            $recommended_max_credits = ($semester == 'Summer') ? 8 : 20;
            $availablecourses = [];
            // Step 2: Get available courses for current semester
            foreach ($allCourses as $course) {
                if (Str::contains(strtolower($course->semester), strtolower($semester))) {
                    $availablecourses[] = $course;
                }
            }

            // Step 3: Filter available courses by prerequisites
            foreach ($availablecourses as $course) {
                // Skip already completed courses
                if (in_array($course->courseid, $doneCourseIds)) {
                    continue;
                }

                // If no prerequisites, recommend directly
                if ($course->prerequisites->isEmpty()) {
                    $postrequisites_count = 0;
                    $postrequisites = $course->prerequisiteFor;
                    foreach ($postrequisites as $post) {
                        $founded = $allCourses->contains($post->courseid);
                        if ($founded) {
                            $postrequisites_count++;
                        }
                    }
                    if ($course->coursetype == 'Major') {
                        $score = 4 + $postrequisites_count;
                        if ($postrequisites) {
                            if ($course->semester == $currentsemester) {
                                $score = $score + 2;
                            }
                        }
                        $offeredcourses[] =  ['courseid' => $course->courseid, 'coursename' => $course->coursename, 'coursecode' => $course->coursecode, 'coursetype' => $course->coursetype, 'credits' => $course->credits, 'corerequisites' => $course->corerequisites, 'postrequisitFor' => $postrequisites_count, 'score' => $score];
                        $majorcount++;
                    } elseif ($course->coursetype == 'Core') {
                        $score = 5 + $postrequisites_count;
                        if ($postrequisites) {
                            if ($course->semester == $currentsemester) {
                                $score = $score + 3;
                            }
                        }
                        $offeredcourses[] =  ['courseid' => $course->courseid, 'coursename' => $course->coursename, 'coursecode' => $course->coursecode, 'coursetype' => $course->coursetype, 'credits' => $course->credits, 'corerequisites' => $course->corerequisites, 'postrequisitFor' => $postrequisites_count, 'score' => $score];
                        $corecount++;
                    } elseif ($course->coursetype == 'Major Elective') {
                        $score = 3 + $postrequisites_count;
                        $offeredcourses[] =  ['courseid' => $course->courseid, 'coursename' => $course->coursename, 'coursecode' => $course->coursecode, 'coursetype' => $course->coursetype, 'credits' => $course->credits, 'corerequisites' => $course->corerequisites, 'postrequisitFor' => $postrequisites_count, 'score' => $score];
                        $melectivecount++;
                    } elseif ($course->coursetype == 'General Elective') {
                        $score = 2 + $postrequisites_count;
                        $offeredcourses[] =  ['courseid' => $course->courseid, 'coursename' => $course->coursename, 'coursecode' => $course->coursecode, 'coursetype' => $course->coursetype, 'credits' => $course->credits, 'corerequisites' => $course->corerequisites, 'postrequisitFor' => $postrequisites_count, 'score' => $score];
                        $gelectivecount++;
                    } else {
                        $score = 1 + $postrequisites_count;
                        $offeredcourses[] =  ['courseid' => $course->courseid, 'coursename' => $course->coursename, 'coursecode' => $course->coursecode, 'coursetype' => $course->coursetype, 'credits' => $course->credits, 'corerequisites' => $course->corerequisites, 'postrequisitFor' => $postrequisites_count, 'score' => $score];
                        $geducationcount++;
                    }
                    continue;
                }
                // Otherwise, check if all prerequisites are done
                $allPrereqsDone = true;
                foreach ($course->prerequisites as $prereq) {
                    $founded = $allCourses->contains($prereq->prerequisitecourseid);
                    if ($founded) {
                        if (!in_array($prereq->prerequisitecourseid, $doneCourseIds)) {
                            $allPrereqsDone = false;
                            break;
                        }
                    }
                }

                if ($allPrereqsDone) {
                    $postrequisites_count = 0;
                    $postrequisites = $course->prerequisiteFor;
                    foreach ($postrequisites as $post) {
                        $founded = $allCourses->contains($post->courseid);
                        if ($founded) {
                            $postrequisites_count++;
                        }
                    }
                    if ($course->coursetype == 'Major') {
                        $score = 4 + $postrequisites_count;
                        if ($postrequisites) {
                            if ($course->semester == $currentsemester) {
                                $score = $score + 2;
                            }
                        }
                        $offeredcourses[] =  ['courseid' => $course->courseid, 'coursename' => $course->coursename, 'coursecode' => $course->coursecode, 'coursetype' => $course->coursetype, 'credits' => $course->credits, 'corerequisites' => $course->corerequisites, 'postrequisitFor' => $postrequisites_count, 'score' => $score];
                        $majorcount++;
                    } elseif ($course->coursetype == 'Core') {
                        $score = 5 + $postrequisites_count;
                        if ($postrequisites) {
                            if ($course->semester == $currentsemester) {
                                $score = $score + 3;
                            }
                        }
                        $offeredcourses[] =  ['courseid' => $course->courseid, 'coursename' => $course->coursename, 'coursecode' => $course->coursecode, 'coursetype' => $course->coursetype, 'credits' => $course->credits, 'corerequisites' => $course->corerequisites, 'postrequisitFor' => $postrequisites_count, 'score' => $score];
                        $corecount++;
                    } elseif ($course->coursetype == 'Major Elective') {
                        $score = 3 + $postrequisites_count;
                        $offeredcourses[] =  ['courseid' => $course->courseid, 'coursename' => $course->coursename, 'coursecode' => $course->coursecode, 'coursetype' => $course->coursetype, 'credits' => $course->credits, 'corerequisites' => $course->corerequisites, 'postrequisitFor' => $postrequisites_count, 'score' => $score];
                        $melectivecount++;
                    } elseif ($course->coursetype == 'General Elective') {
                        $score = 2 + $postrequisites_count;
                        $offeredcourses[] =  ['courseid' => $course->courseid, 'coursename' => $course->coursename, 'coursecode' => $course->coursecode, 'coursetype' => $course->coursetype, 'credits' => $course->credits, 'corerequisites' => $course->corerequisites, 'postrequisitFor' => $postrequisites_count, 'score' => $score];
                        $gelectivecount++;
                    } else {
                        $score = 1 + $postrequisites_count;
                        $offeredcourses[] =  ['courseid' => $course->courseid, 'coursename' => $course->coursename, 'coursecode' => $course->coursecode, 'coursetype' => $course->coursetype, 'credits' => $course->credits, 'corerequisites' => $course->corerequisites, 'postrequisitFor' => $postrequisites_count, 'score' => $score];
                        $geducationcount++;
                    }
                }
            }
            $offeredCollection = collect($offeredcourses);

            $total_credits = 0;
            $alreadyAdded = [];

            usort($offeredcourses, fn($a, $b) => $b['score'] <=> $a['score']);
            $offeredCollection = collect($offeredcourses);
            $offeredIds = $offeredCollection->pluck('courseid')->toArray();

            foreach ($offeredcourses as $course) {
                $courseId = $course['courseid'];

                if (in_array($courseId, $alreadyAdded)) {
                    continue;
                }
                $courseCredits = $course['credits'];
                $coreqCredits = 0;
                $coreqCourse = null;
                $coreqId = null;
                $hasCoreqInOffered = false;

                // Check if course has a valid corequisite
                if (!empty($course['corerequisites'])) {
                    foreach ($course['corerequisites'] as $coreq) {
                        if ($coreq['corerequisiteid'] != 0) {
                            $coreqId = $coreq['corerequisiteid'];

                            // Corequisite is in the offered list
                            if (in_array($coreqId, $offeredIds)) {
                                $hasCoreqInOffered = true;

                                // If not already added
                                if (!in_array($coreqId, $alreadyAdded)) {
                                    $coreqCourse = $offeredCollection->firstWhere('courseid', $coreqId);
                                    $coreqCredits = $coreqCourse['credits'];
                                } else {
                                    // Corequisite already added → can't take main course alone
                                    $coreqCourse = null;
                                }

                                break; // Only one corequisite needed
                            }
                        }
                    }
                }

                // Logic 1: Corequisite exists in offered → must add both or skip both
                if ($hasCoreqInOffered) {
                    if ($coreqCourse && $total_credits + $courseCredits + $coreqCredits <= $recommended_max_credits) {
                        $doneCourseIds[] = $course['courseid'];
                        $toberegistered[] = $course;
                        $toberegistered[] = $coreqCourse;
                        $alreadyAdded[] = $courseId;
                        $alreadyAdded[] = $coreqCourse['courseid'];
                        $total_credits += $courseCredits + $coreqCredits;
                    }
                    continue; // Don't take course alone if corequisite exists
                }

                // Logic 2: No corequisite in offered → take alone if fits
                if ($total_credits + $courseCredits <= $recommended_max_credits) {
                    $doneCourseIds[] = $course['courseid'];
                    $toberegistered[] = $course;
                    $alreadyAdded[] = $courseId;
                    $total_credits += $courseCredits;
                }
            }
            $remaining_untaken_courses = array_diff($CoursesIds, $doneCourseIds);
            $courses = collect($toberegistered);
            if ($courses->isEmpty()) {
                $remaining_semesters--;
                // Skip adding this semester if no courses are to be registered
                continue; // Skip to the next iteration
            } else {
                // Add to $semester_courses array if $toberegistered is not empty
                $semester_courses[] = [
                    'semester' => $semester,
                    'year' => $academic_year,
                    'courses' => $toberegistered
                ];
            }

            $courses = collect($toberegistered);
            // Add the semester and its courses only once
            $offeredcourses_per_semester[] = [
                'semester' => $semester,
                'year' => $academic_year,
                'courses' => $offeredcourses
            ];
            $offeredcourses = [];
            $toberegistered = [];
        }
        // Find courses that are in $CoursesIds but not in $doneCourseIds

        $estimated_graduation_date = [
            'semester' => $semester_courses[sizeof($semester_courses) - 1]['semester'],
            'year' => $semester_courses[sizeof($semester_courses) - 1]['year']
        ];
        // Return recommended course IDs for now
        return response()->json([
            'doneCourses' => count($doneCourseIds),
            'untaken_courses' => $remaining_untaken_courses,
            'graduation_info' => $estimated_graduation_date,
            'offeredCourses' => $offeredcourses_per_semester,
            'recommendedforRegestration' => $semester_courses,
            'completedCredits' => $completedCredits,
            'total_credits' => $total_credit_department,
            'remaining_semesters' => $remaining_semesters,
            'recommended_max_credits' => $recommended_max_credits
        ]);
    }

    private function getcurrentsemester()
    {
        $month = now()->month;
        $year = now()->year;

        if ($month >= 9 && $month <= 12) {
            $semester = 'Spring';
            $academicYear = $year . '-' . ($year + 1);
        } elseif ($month >= 2 && $month <= 5) {
            $semester = 'Fall';
            $academicYear = $year . '-' . ($year + 1);
        } else {
            $semester = 'Summer';
            $academicYear = $year . '-' . ($year + 1);
        }

        return [
            'semester' => $semester,
            'year' => $year
        ];
    }
}
