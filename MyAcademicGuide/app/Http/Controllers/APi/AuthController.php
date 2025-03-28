<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('userid', $request->userid)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'message' => ['The provided credentials are incorrect']
            ]);
        }
        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'message' => ['The provided credentials are incorrect']
            ]);
        }
        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json(['token' => $token, 'usertype' => $user->usertype]);
    }
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([
            'message' => 'Logout out successfully'
        ]);
    }
}
