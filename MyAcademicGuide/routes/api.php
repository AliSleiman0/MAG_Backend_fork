<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\APi\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/index', [DashboardController::class, 'index'])->middleware('auth:sanctum');
