<?php

namespace App\Http\Controllers\APi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
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
    
            // Get all courses in the student's department
            $allCourses = $student->department->courses;
    
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
    
            // Step 2: Get available courses for current semester
            foreach ($allCourses as $course) {
                if (Str::contains(strtolower($course->semester), strtolower($currentsemester))) {
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
                        $offeredcourses[] =  ['courseid' => $course->courseid, 'coursename' => $course->coursename, 'coursecode' => $course->coursecode, 'coursetype' => $course->coursetype, 'credits' => $course->credits, 'corerequisites' => $course->$course->corerequisites, 'postrequisitFor' => $postrequisites_count, 'score' => $score];
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
            $completedCredits = $student->enrollments
                ->whereIn('status', ['Passed', 'Registered'])
                ->sum(fn($enrollment) => $enrollment->course->credits);
            $total_credit_department = $student->department->courses->sum(fn($course) => $course->credits);
            $remaining_credits = $total_credit_department - $completedCredits;
            $remaining_semesters = ceil($remaining_credits / 13);
            $recommended_max_credits = ceil($remaining_credits / $remaining_semesters);
    
            $offeredCollection = collect($offeredcourses);
    
            $toberegistered = [];
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
                    $toberegistered[] = $course;
                    $alreadyAdded[] = $courseId;
                    $total_credits += $courseCredits;
                }
            }
    
    
    
            // Return recommended course IDs for now
            return response()->json([
                'doneCourses' => $doneCourseIds,
                'offeredCourses' => $offeredcourses,
                'recommendedforRegestration' => $toberegistered,
                'completedCredits' => $completedCredits,
                'total_credits' => $total_credit_department,
                'remaining_semesters' => $remaining_semesters,
                'recommended_max_credits' => $recommended_max_credits
            ]);
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
    }  */
}
