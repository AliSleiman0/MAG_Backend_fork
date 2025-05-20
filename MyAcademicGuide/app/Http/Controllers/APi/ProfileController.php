<?php

namespace App\Http\Controllers\APi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class ProfileController extends Controller
{

    public function profile(Request $request)
    {
        $user = $request->user()->userid;
        $profileinfo = User::with(['campus', 'student.department.school'])->where('userid', $user)->first();
        return response()->json([
            'userid' => $profileinfo->userid,
            'fullname' => $profileinfo->fullname,
            'email' => $profileinfo->email,
            'campusname' => $profileinfo->campus->campusname,
            'department' => $profileinfo->student->department->departmentname,
            'schoolname' => $profileinfo->student->department->school->schoolname,
            'image' => $profileinfo->image,
        ]);
    }
    public function addimage(Request $request, User $user)
    {
        $validated = $request->validate([
            'image' => 'required|string' // or 'required|image' if it's a file
        ]);

        $user->update([
            'image' => $validated['image']
        ]);

        return response()->json([
            'message' => 'Image updated successfully.'
        ]);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function deleteimage(Request $request, User $user)
    {
        if ($user->image == null) {
            return response()->json([
                'message' => 'No image to delete.',
            ]);
        }
        $user->update(['image' => null]);
        return response()->json([
            'message' => 'Image deleted successfully.',
        ]);
    }
    public function resetpassword(Request $request, User $user)
    {
        $request->validate([
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'regex:/[A-Z]/', // At least one uppercase letter
                'regex:/[a-z]/', // At least one lowercase letter
                'regex:/[0-9]/', // At least one number
                'regex:/[@$!%*?&.]/', // At least one special character
            ],
        ]);
        $user->update(['password' => Hash::make($request->password)]);
        return response()->json(['message' => 'Password updated successfully.']);
    }
    public function getadvisors(Request $request)
    {
        $finalform = [];
        $student = $request->user()->userid;
        $studentinfo = User::where('userid', $student)->with(['student.department'])->get(['campusid', 'userid']);
        $studentschool = $studentinfo[0]->student->department->schoolid;
        $advisor = User::where('usertype', 'Advisor')->where('campusid', $studentinfo[0]->campusid)->with('advisor')->whereHas('advisor', function ($query) use ($studentschool) {
            $query->where('schoolid', $studentschool);
        })->get(['userid', 'fullname', 'email', 'image']);
        $finalform = $advisor->map(function ($advisor) {
            return [
                'userid' => $advisor->userid,
                'fullname' => $advisor->fullname,
                'email' => $advisor->email,
                'image' => $advisor->image,
            ];
        });
        return $finalform;
    }
}
