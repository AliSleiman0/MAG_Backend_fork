<?php

use App\Http\Controllers\APi\AdvisorController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\APi\DashboardController;
use App\Http\Controllers\APi\ProfileController;
use App\Http\Controllers\APi\POSController;
use App\Http\Controllers\APi\CustomizedPOSController;
use App\Http\Controllers\APi\AutomatedPOSController;
use App\Http\Controllers\APi\TimeTableController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Profiler\Profile;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/index', [DashboardController::class, 'index'])->middleware('auth:sanctum');
Route::get('/profile', [ProfileController::class, 'profile'])->middleware('auth:sanctum');
Route::put('/profile/deleteimage/{user}', [ProfileController::class, 'deleteimage'])->middleware('auth:sanctum');
Route::put('/profile/addimage/{user}', [ProfileController::class, 'addimage'])->middleware('auth:sanctum');
Route::put('/profile/updatepassword', [ProfileController::class, 'updatepassword'])->middleware('auth:sanctum');
Route::get('/pos', action: [POSController::class, 'pos'])->middleware('auth:sanctum')->middleware('auth:sanctum');
Route::post('/verify-new-password', [ProfileController::class, 'verifyNewPasswordAndSendCode'])->middleware('auth:sanctum');;
Route::post('/update-password', [ProfileController::class, 'updatePassword'])->middleware('auth:sanctum');
Route::get('/automated_pos', action: [AutomatedPOSController::class, 'automated_pos'])->middleware('auth:sanctum');
Route::get('/rest_pos', action: [AutomatedPOSController::class, 'rest_pos'])->middleware('auth:sanctum');
Route::post('/CustomizedPOS', action: [CustomizedPOSController::class, 'CustomizedPOS'])->middleware('auth:sanctum');
Route::post('/setschedule', action: [TimeTableController::class, 'setschedule'])->middleware('auth:sanctum');
Route::get('/getadvisors', action: [ProfileController::class, 'getadvisors'])->middleware('auth:sanctum');
Route::get('/getstudents/{user}', action: [AdvisorController::class, 'getstudents'])->middleware('auth:sanctum');
Route::get('/advisorprofile/{user}', action: [AdvisorController::class, 'advisorprofile'])->middleware('auth:sanctum');
Route::post('/smartschedule', action: [TimeTableController::class, 'smartschedule'])->middleware('auth:sanctum');
Route::get('/getProfileById/{user}', action: [AdvisorController::class, 'profile']);
Route::put('/profile/resetpassword/{user}', [ProfileController::class, 'resetpassword'])->middleware('auth:sanctum');
Route::get('/automated_pos_for_advisor/{studentid}', action: [AdvisorController::class, 'automated_pos_for_advisor'])->middleware('auth:sanctum');
