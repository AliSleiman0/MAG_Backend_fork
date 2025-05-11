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
    public function verifyNewPasswordAndSendCode(Request $request)
    {
        $user = User::find($request->user()->userid);
        // Validate the new password
        $request->validate([
            'new_password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'regex:/[A-Z]/', // At least one uppercase letter
                'regex:/[a-z]/', // At least one lowercase letter
                'regex:/[0-9]/', // At least one number
                'regex:/[@$!%*?&]/', // At least one special character
            ],
        ]);

        // Generate a 6-digit verification code
        $verificationCode = mt_rand(100000, 999999);

        // Store verification code and new password in session
        Session::put('verification_code', $verificationCode);
        Session::put('new_password', Hash::make($request->new_password)); // Hash new password for security
        Session::put('verified_user_id', $user->id);

        // Send the verification code via email
        Mail::raw("Your verification code for password change is: $verificationCode", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Password Change Verification Code');
        });

        return response()->json(['message' => 'Verification code sent to your email.', $verificationCode]);
    }
    public function updatePassword(Request $request)
    {
        // Validate verification code
        $request->validate([
            'verification_code' => 'required|numeric',
        ]);

        // Check if the verification code matches the stored one
        if (Session::get('verification_code') != $request->verification_code) {
            return response()->json(['error' => 'Invalid verification code.'], 400);
        }

        // Retrieve user from session
        $user = User::find(Session::get('verified_user_id'));
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        // Update the password from session
        $user->update([
            'password' => Session::get('new_password'),
        ]);

        // Clear session data after updating password
        Session::forget('verification_code');
        Session::forget('new_password');
        Session::forget('verified_user_id');

        return response()->json(['message' => 'Password updated successfully.']);
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
                'regex:/[@$!%*?&]/', // At least one special character
            ],
        ]);
        $user->update(['password' => $request->password]);
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
