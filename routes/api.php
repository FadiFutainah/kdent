<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SpecializationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TreatmentPlanController;
use App\Http\Controllers\TreatmentSessionController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\DoctorFinanceController;
use App\Http\Controllers\SupplierItemsController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\AppointmentController;

Route::post('/register', [AuthController::class, 'register']); // للمريض فقط
Route::post('/verify', [AuthController::class, 'verify']);
Route::post('/resendOtp', [AuthController::class, 'resendOtp']);     // للمريض فقط
Route::post('/login', [AuthController::class, 'login']);       // للجميع مع إرسال الرول

Route::middleware('auth:sanctum')->group(function () {
Route::post('/logout', [AuthController::class, 'logout']); });// تسجيل الخروج


Route::middleware(['auth:sanctum', 'role:patient'])->group(function () {
Route::get('/specializations', [SpecializationController::class,'index'])->middleware('role:patient');//عرض الاختصاصات 
Route::get('/specializations/{id}', [SpecializationController::class,'show']);//عرض تفاصيل الاختصاص 
Route::post('/available-slots', [PatientController::class, 'getAvailableSlotsForDays']);
Route::post('/book-appointment', [PatientController::class, 'bookAppointment']);
});


Route::middleware(['auth:sanctum', 'role:patient|secretary'])->group(function () {
Route::get('/specializations', [SpecializationController::class,'index']);//عرض الاختصاصات 
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function(){
    Route::post('/create-employee', [AdminController::class, 'createEmployee']);
});

Route::middleware(['auth:sanctum', 'role:doctor'])->group(function(){
    Route::post('/addAvailableTime', [DoctorController::class, 'addAvailableTime']);
    Route::post('/updateAvailableTime/{id}', [DoctorController::class, 'updateAvailableTime']);
    Route::delete('/deleteAvailableTime/{id}', [DoctorController::class, 'deleteAvailableTime']);
    Route::get('/myPatients', [DoctorController::class, 'myPatients']);
    Route::get('/todayAppointments', [DoctorController::class, 'todayAppointments']);
    Route::get('/upcomingAppointments', [DoctorController::class, 'upcomingAppointments']);

});

Route::middleware(['auth:sanctum', 'role:secretary'])->group(function () {
    Route::post('/secretary/doctors', [AppointmentController::class, 'listDoctorsBySpecialization']);
    Route::post('/secretary/doctors/{doctorId}/available-slots', [AppointmentController::class, 'availableSlotsByDoctor']);
    Route::get('/secretary/appointments/scheduled', [AppointmentController::class, 'listScheduledBySecretary']);
    Route::get('/secretary/appointments/confirmed', [AppointmentController::class, 'listConfirmedBySecretary']);
    Route::post('/appointments/secretary', [AppointmentController::class, 'bookBySecretary']);
    Route::post('/appointments/{appointmentId}/confirm', [AppointmentController::class, 'confirmBySecretary']);
});

Route::middleware(['auth:sanctum', 'role:admin|accountant'])->group(function () {
    Route::post('/doctors/{doctorId}/payments', [DoctorFinanceController::class, 'recordPayment']);
    Route::get('/doctors/{doctorId}/finance/summary', [DoctorFinanceController::class, 'summary']);
    Route::post('/exchange-rates/refresh', [ExchangeRateController::class, 'refresh']);
});

Route::middleware(['auth:sanctum', 'role:doctor|admin|accountant'])->group(function () {
    Route::get('/exchange-rates/current', [ExchangeRateController::class, 'current']);
    Route::get('/exchange-rates/history', [ExchangeRateController::class, 'history']);
});

Route::middleware(['auth:sanctum', 'role:patient|doctor'])->group(function () {
    Route::get('/my/treatment-plans', [TreatmentPlanController::class, 'myPlans']);
    Route::get('/treatment-plans/show/{planId}', [TreatmentPlanController::class, 'show']);
    Route::get('/treatment-plans/{planId}/showItem/{itemId}', [TreatmentPlanController::class, 'showItem']);
    Route::get('/treatment-plans/{planId}/items/{itemId}/showSession/{sessionId}', [TreatmentPlanController::class, 'showSession']);
});

Route::middleware(['auth:sanctum', 'role:doctor'])->group(function () {
    Route::get('/my/finance/summary', [DoctorFinanceController::class, 'mySummary']);
    Route::post('/treatment-plans/search', [TreatmentPlanController::class, 'search']);
    Route::post('/treatment-plans', [TreatmentPlanController::class, 'store']);
    Route::post('/treatment-plans/{planId}', [TreatmentPlanController::class, 'update']);
    Route::post('/treatment-plans/{planId}/items', [TreatmentPlanController::class, 'addItem']);
    Route::post('/treatment-plans/{planId}/items/{itemId}', [TreatmentPlanController::class, 'updateItem']);
    Route::delete('/treatment-plans/{planId}/items/{itemId}', [TreatmentPlanController::class, 'deleteItem']);
    Route::post('/plan-items/{itemId}/treatment-sessions', [TreatmentSessionController::class, 'store']);
    Route::post('/treatment-sessions/{sessionId}', [TreatmentSessionController::class, 'update']);
    Route::patch('/treatment-sessions/{sessionId}/complete', [TreatmentSessionController::class, 'complete']);
});


Route::middleware(['auth:sanctum', 'role:storekeeper'])->group(function () {
    Route::post('/suppliers', [SupplierItemsController::class, 'store']);
    Route::post('/purchase', [InventoryTransactionController::class, 'purchase']);
    Route::post('/consume', [InventoryTransactionController::class, 'consume']);
    Route::post('/storeitems', [SupplierItemsController::class, 'stores']);
});
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
