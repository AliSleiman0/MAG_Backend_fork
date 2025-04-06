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
        $profileinfo = User::with('campus')->where('userid', $user)->first();
        return response()->json([
            'userid' => $profileinfo->userid,
            'fullname' => $profileinfo->fullname,
            'email' => $profileinfo->email,
            'campusname' => $profileinfo->campus->campusname,
            'imagepath' => $profileinfo->imagepath,
        ]);
    }

    public function addimage(Request $request)
    {
        $user = User::find($request->user()->userid);
        $user->update(['imagepath' => $request->imagepath]);
        return response()->json([
            'message' => 'Image updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteimage(Request $request)
    {
        $user = User::find($request->user()->userid);
        if ($user->imagepath == null) {
            return response()->json([
                'message' => 'No image to delete.',
            ]);
        }
        $user->update(['imagepath' => null]);
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
}
