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
        $priority = [
            'General Education' => 4,
            'General Elective'  => 3,
            'Major Elective'    => 2,
            'Major'             => 1,
            'Core'              => 0,
        ];
        // If from Collection or uncertain source
        $validCourses = collect($validCourses)->toArray();
        usort($validCourses, function ($a, $b) use ($priority) {
            return $priority[$a['coursetype']] <=> $priority[$b['coursetype']];
        });
        $finalSections = [];
        $courseCount = count($validCourses);
        $allPossibleSections = [];

        foreach ($validCourses as $i => $course) {
            $possibleSections = [];

            foreach ($course['sections'] as $currentSection) {
                $conflictCount = 0;
                $hasHardConflict = false;
                $sectionDays = explode(',', $currentSection['days']);

                // 🔁 Check for hard conflicts against finalized selections (regardless of position)
                foreach ($finalSections as $selectedSection) {
                    $selectedDays = explode(',', $selectedSection['days']);
                    $overlappingDays = array_intersect($sectionDays, $selectedDays);

                    if (!empty($overlappingDays)) {
                        if (
                            $currentSection['startTime']->lt($selectedSection['endTime']) &&
                            $currentSection['endTime']->gt($selectedSection['startTime'])
                        ) {
                            $hasHardConflict = true;
                            break;
                        }
                    }
                }

                // 🔁 Check for soft conflicts (conflict count) with *all other course sections*
                for ($j = 0; $j < $courseCount; $j++) {
                    if ($j === $i) continue; // don't compare with itself

                    foreach ($validCourses[$j]['sections'] as $otherSection) {
                        $otherDays = explode(',', $otherSection['days']);
                        $overlappingDays = array_intersect($sectionDays, $otherDays);

                        if (!empty($overlappingDays)) {
                            if (
                                $currentSection['startTime']->lt($otherSection['endTime']) &&
                                $currentSection['endTime']->gt($otherSection['startTime'])
                            ) {
                                //$hasHardConflict=true;
                                $conflictCount++;
                            }
                        }
                    }
                }

                if (!$hasHardConflict) {
                    $possibleSections[] = array_merge($currentSection, ['conflictCount' => $conflictCount]);
                }
            }
            // Select best
            if (!empty($possibleSections)) {
                usort($possibleSections, function ($a, $b) {
                    if ($a['conflictCount'] !== $b['conflictCount']) {
                        return $a['conflictCount'] <=> $b['conflictCount'];
                    }
                    return $a['startTime']->lt($b['startTime']) ? -1 : 1;
                });
                if (!empty($possibleSections)) {
                    // Find the minimum conflict count
                    $minConflictCount = min(array_column($possibleSections, 'conflictCount'));

                    // Filter to keep only sections with that minimum conflict count
                    $possibleSections = array_filter($possibleSections, function ($section) use ($minConflictCount) {
                        return $section['conflictCount'] === $minConflictCount;
                    });

                    // Reindex array to keep it clean
                    $possibleSections = array_values($possibleSections);
                }
                $allPossibleSections[] = [
                    'courseid'   => $course['courseid'],
                    'coursecode' => $course['coursecode'],
                    'coursename' => $course['coursename'],
                    'sections'   => $possibleSections,
                ];
                $chosen = $possibleSections[0];
                $finalSections[] = array_merge($chosen, [
                    'courseid'   => $course['courseid'],
                    'coursecode' => $course['coursecode'],
                    'coursename' => $course['coursename'],
                ]);
            }
        }
        $schedule = $this->generateOptimalSchedule($allPossibleSections);
        return $schedule; // Return or further process the filtered courses

    }
    private function generateOptimalSchedule($allPossibleSections)
    {
        // First ensure ALL courses are processed
        $singleSectionCourses = [];
        $multiSectionCourses = [];

        foreach ($allPossibleSections as $course) {
            if (count($course['sections']) === 1) {
                $singleSectionCourses[] = $course;
            } else {
                $multiSectionCourses[] = $course;
            }
        }

        // Process single-section courses first (including Course 110)
        $schedule = [];
        foreach ($singleSectionCourses as $course) {
            $schedule[] = array_merge($course['sections'][0], [
                'courseid' => $course['courseid'],
                'coursecode' => $course['coursecode'],
                'coursename' => $course['coursename'],
            ]);
        }

        // Process multi-section courses with STRONG same-day preference
        foreach ($multiSectionCourses as $course) {
            $bestSection = $this->findBestSection($schedule, $course['sections']);
            $schedule[] = array_merge($bestSection, [
                'courseid' => $course['courseid'],
                'coursecode' => $course['coursecode'],
                'coursename' => $course['coursename'],
            ]);
        }

        return $schedule;
    }

    private function findBestSection($currentSchedule, $possibleSections)
    {
        $bestScore = PHP_INT_MAX;
        $bestSection = null;

        foreach ($possibleSections as $section) {
            $score = $this->calculateSectionScore($currentSchedule, $section);

            if ($score < $bestScore) {
                $bestScore = $score;
                $bestSection = $section;
            }
        }

        return $bestSection;
    }

    function calculateSectionScore($schedule, $newSection)
    {
        $score = 0;
        $newDays = $newSection['daysOfWeek'];
        $newStart = strtotime($newSection['startTime']);
        $newEnd = strtotime($newSection['endTime']);

        // 1. Day Distribution Scoring
        $existingDayCounts = array_fill(1, 7, 0); // Track courses per day (1=Mon..7=Sun)
        $sharedDayBonus = 0;

        foreach ($schedule as $existing) {
            foreach ($existing['daysOfWeek'] as $day) {
                $existingDayCounts[$day]++;
            }
        }

        // 2. Balanced Shared Day Logic
        foreach ($newDays as $day) {
            if ($existingDayCounts[$day] > 0) {
                // Moderate bonus for first shared day, decreasing returns for additional shares
                $sharedDayBonus += max(50, 100 - ($existingDayCounts[$day] * 20));
            }
        }
        $score -= $sharedDayBonus; // Lower score is better

        // 3. Time Gap Optimization (minutes)
        $minGap = PHP_INT_MAX;
        foreach ($schedule as $existing) {
            $commonDays = array_intersect($newDays, $existing['daysOfWeek']);
            if (empty($commonDays)) continue;

            $existingStart = strtotime($existing['startTime']);
            $existingEnd = strtotime($existing['endTime']);

            if ($newStart >= $existingEnd) {
                $gap = ($newStart - $existingEnd) / 60; // Convert to minutes
            } elseif ($newEnd <= $existingStart) {
                $gap = ($existingStart - $newEnd) / 60;
            } else {
                $gap = 0; // Hard conflict
            }

            // Progressive gap scoring:
            // - Ideal: 60-120 minute gaps
            // - Penalize <30min or >3hr gaps
            if ($gap > 0) {
                if ($gap < 30) {
                    $gapScore = 50 + (30 - $gap) * 2; // Steep penalty for tight gaps
                } elseif ($gap > 180) {
                    $gapScore = 30 + ($gap - 180) * 0.5; // Small penalty for very large gaps
                } else {
                    $gapScore = -100; // Bonus for ideal gaps
                }
                $minGap = min($minGap, $gapScore);
            }
        }
        $score += ($minGap !== PHP_INT_MAX) ? $minGap : 0;

        // 4. Time of Day Preference
        $hour = date('G', $newStart);
        if ($hour < 9) {
            $score += 20; // Slight penalty for very early classes
        } elseif ($hour > 16) {
            $score += 30; // Moderate penalty for late classes
        }

        // 5. Course Density Penalty
        foreach ($newDays as $day) {
            $score += $existingDayCounts[$day] * 15; // Linear penalty for busy days
        }

        return $score;
    }
    private function overlapsWithBreak($section, $break)
    {
        return $section['startTime']->lt($break['end']) && $section['endTime']->gt($break['start']);
    }
}
