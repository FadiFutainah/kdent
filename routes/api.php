<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SpecializationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\AdminController;

Route::post('/register', [AuthController::class, 'register']); // للمريض فقط
Route::post('/verify', [AuthController::class, 'verify']);
Route::post('/resendOtp', [AuthController::class, 'resendOtp']);     // للمريض فقط
Route::post('/login', [AuthController::class, 'login']);       // للجميع مع إرسال الرول

Route::middleware('auth:sanctum')->group(function () {
Route::post('/logout', [AuthController::class, 'logout']); // تسجيل الخروج
Route::get('/specializations', [SpecializationController::class,'index'])->middleware('role:patient');//عرض الاختصاصات 
Route::get('/specializations/{id}', [SpecializationController::class,'show']);//عرض تفاصيل الاختصاص 
Route::post('/available-slots', [PatientController::class, 'getAvailableSlotsForDays']);
Route::post('/book-appointment', [PatientController::class, 'bookAppointment']);
//Route::get('/specializations/{id}/doctor', [SpecializationController::class,'getDoctor']);
});
Route::middleware(['auth:sanctum', 'role:admin'])
    ->post('/create-employee', [AdminController::class, 'createEmployee']);

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
