<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\APi\DashboardController;
use App\Http\Controllers\APi\ProfileController;
use App\Http\Controllers\APi\POSController;
use App\Http\Controllers\APi\AutomatedPOSController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/index', [DashboardController::class, 'index'])->middleware('auth:sanctum');
Route::get('/profile', [ProfileController::class, 'profile'])->middleware('auth:sanctum');
Route::put('/profile/deleteimage', [ProfileController::class, 'deleteimage'])->middleware('auth:sanctum');
Route::put('/profile/addimage', [ProfileController::class, 'addimage'])->middleware('auth:sanctum');
Route::put('/profile/updatepassword/{user}', [ProfileController::class, 'updatepassword'])->middleware('auth:sanctum');
Route::get('/pos', action: [POSController::class, 'pos'])->middleware('auth:sanctum')->middleware('auth:sanctum');
Route::post('/verify-new-password', [ProfileController::class, 'verifyNewPasswordAndSendCode'])->middleware('auth:sanctum');;
Route::post('/update-password', [ProfileController::class, 'updatePassword'])->middleware('auth:sanctum');;
Route::get('/automated_pos', action: [AutomatedPOSController::class, 'automated_pos'])->middleware('auth:sanctum')->middleware('auth:sanctum');
