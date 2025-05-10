<?php

namespace App\Http\Controllers\APi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

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
        $students = User::where('usertype', 'Student')->where('campusid', $advisors[0]->campusid)->with('student.department')->whereHas('student.department', function ($query) use ($advisorschool, $queryfilter) {
            $query->where('schoolid', $advisorschool);
            if ($queryfilter) {
                $query->where('departmentid', $queryfilter);
            }
        })->when($queryString, function ($query) use ($queryString) {
            $query->where(function ($q) use ($queryString) {
                $q->where('fullname', 'LIKE', "%{$queryString}%")
                    ->orWhere('userid', 'LIKE', "%{$queryString}%");
            });
        })->get(['userid', 'fullname', 'email', 'image']);
        $finalform = $students->map(function ($students) {
            return [
                'userid' => $students->userid,
                'fullname' => $students->fullname,
                'email' => $students->email,
                'image' => $students->image,
                'departmentid' => $students->student->department->departmentid,
                'departmentname' => $students->student->department->departmentname,
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
            'usertype'=>$profileinfo->usertype,
            'fullname' => $profileinfo->fullname,
            'email' => $profileinfo->email,
            'campusname' => $profileinfo->campus->campusname,
            'image' => $profileinfo->image,
        ]);
    }
}
