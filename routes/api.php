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
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\MedicalReportController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\SecretaryController;

Route::post('/register', [AuthController::class, 'register']); // للمريض فقط
Route::post('/verify', [AuthController::class, 'verify']);
Route::post('/resendOtp', [AuthController::class, 'resendOtp']);     // للمريض فقط
Route::post('/login', [AuthController::class, 'login']);       // للجميع مع إرسال الرول

Route::middleware('auth:sanctum')->group(function () {
Route::post('/logout', [AuthController::class, 'logout']); });// تسجيل الخروج


Route::middleware(['auth:sanctum', 'role:patient'])->group(function () {
Route::get('/specializations', [SpecializationController::class,'index'])->middleware('role:patient');//عرض الاختصاصات 
Route::get('/specializations/{id}', [SpecializationController::class,'show']);//عرض تفاصيل الاختصاص 
Route::get('/specializations/{id}/doctors', [SpecializationController::class,'getDoctorsBySpecialization']);//عرض الأطباء حسب الاختصاص
Route::get('/available-slots/{doctorId}', [PatientController::class, 'getAvailableSlotsForDays']);
Route::post('/book-appointment', [PatientController::class, 'bookAppointment']);
});


Route::middleware(['auth:sanctum', 'role:patient|secretary'])->group(function () {
Route::get('/specializations', [SpecializationController::class,'index']);//عرض الاختصاصات 
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function(){
    Route::post('/create-employee', [AdminController::class, 'createEmployee']);
    Route::get('/Allpatients', [PatientController::class, 'listAllPatients']);

});

Route::middleware(['auth:sanctum', 'role:doctor'])->group(function(){
    Route::post('/addAvailableTime', [DoctorController::class, 'addAvailableTime']);
    Route::post('/updateAvailableTime/{id}', [DoctorController::class, 'updateAvailableTime']);
    Route::delete('/deleteAvailableTime/{id}', [DoctorController::class, 'deleteAvailableTime']);
    Route::get('/myPatients', [DoctorController::class, 'myPatients']);
    Route::get('/todayAppointments', [DoctorController::class, 'todayAppointments']);
    Route::get('/upcomingAppointments', [DoctorController::class, 'upcomingAppointments']);
    Route::post('/material-requests', [DoctorController::class, 'store']);

});


/*Route::middleware(['auth:sanctum', 'role:secretary|patient'])->group(function () {

    Route::get('/doctors/{doctorId}/available-slots', [AppointmentController::class, 'availableSlotsByDoctor']);
});*/

Route::middleware(['auth:sanctum', 'role:secretary'])->group(function () {
    Route::get('/secretary/doctors/all', [AppointmentController::class, 'listAllDoctorsForSecretary']);
    Route::post('/secretary/doctors', [AppointmentController::class, 'listDoctorsBySpecialization']);
    Route::post('/secretary/doctors/{doctorId}/available-slots', [AppointmentController::class, 'availableSlotsByDoctor']);
    Route::get('/secretary/doctors/{doctorId}/patients', [SecretaryController::class, 'doctorPatients']);
    Route::get('/secretary/doctors/{doctorId}/appointments/today', [SecretaryController::class, 'doctorTodayAppointments']);
    Route::get('/secretary/appointments/scheduled', [AppointmentController::class, 'listScheduledBySecretary']);
    Route::get('/secretary/appointments/confirmed', [AppointmentController::class, 'listConfirmedBySecretary']);
    Route::post('/appointments/secretary', [AppointmentController::class, 'bookBySecretary']);
    Route::post('/appointments/{appointmentId}/confirm', [AppointmentController::class, 'confirmBySecretary']);
    Route::post('/appointments/{appointmentId}/cancel', [AppointmentController::class, 'cancelBySecretary']);
});

Route::middleware(['auth:sanctum', 'role:doctor|secretary'])->group(function () {
    Route::post('/patients/search', [DoctorController::class, 'searchPatients']);
});

Route::middleware(['auth:sanctum', 'role:admin|accountant'])->group(function () {
    Route::post('/doctors/{doctorId}/payments', [DoctorFinanceController::class, 'recordPayment']);
    Route::get('/doctors/{doctorId}/finance/summary', [DoctorFinanceController::class, 'summary']);
    Route::post('/exchange-rates/refresh', [ExchangeRateController::class, 'refresh']);
    Route::get('/index', [InvoiceController::class, 'index']);//عرض فواتير المورد
    Route::post('/invoices/{id}/approve', [InvoiceController::class, 'approve']);//اعتماد الفاتورة
   // Route::post('/invoices/{id}/mark-as-paid', [InvoiceController::class, 'markAsPaid']);//وضع علامة مدفوعة على الفاتورة
    Route::get('/invoices/{id}/print', [InvoiceController::class, 'print']);//طباعة الفاتورة
    Route::post('/invoices/{id}/pay', [InvoiceController::class, 'pay']);//دفع الفاتورة
    Route::post('/invoices/{id}/apply-discount', [InvoiceController::class, 'applyDiscount']);//تطبيق الخصم
});


Route::middleware(['auth:sanctum', 'role:doctor|admin|accountant'])->group(function () {
    Route::get('/exchange-rates/current', [ExchangeRateController::class, 'current']);
    Route::get('/exchange-rates/history', [ExchangeRateController::class, 'history']);

});

Route::middleware(['auth:sanctum', 'role:patient|doctor|secretary'])->group(function () {
    Route::get('/my/treatment-plans', [TreatmentPlanController::class, 'myPlans']);
    Route::get('/treatment-plans/show/{planId}', [TreatmentPlanController::class, 'show']);
    Route::get('/treatment-plans/{planId}/showItem/{itemId}', [TreatmentPlanController::class, 'showItem']);
    Route::get('/treatment-plans/{planId}/items/{itemId}/showSession/{sessionId}', [TreatmentPlanController::class, 'showSession']);
    Route::get('/medical-reports', [MedicalReportController::class, 'index']);
    Route::get('/medical-reports/{reportId}', [MedicalReportController::class, 'show']);
    Route::get('/medical-records/{patientId}', [MedicalRecordController::class, 'show']);
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
    Route::post('/medical-reports', [MedicalReportController::class, 'store']);
    Route::post('/medical-records/{patientId}', [MedicalRecordController::class, 'update']);
});


Route::middleware(['auth:sanctum', 'role:storekeeper'])->group(function () {
    Route::post('/suppliers', [SupplierItemsController::class, 'store']);
    Route::post('/purchase', [InventoryTransactionController::class, 'purchase']);
    Route::post('/consume', [InventoryTransactionController::class, 'consume']);
    Route::post('/returnItems', [InventoryTransactionController::class, 'returnItems']);
    Route::post('/storeitems', [SupplierItemsController::class, 'stores']);
    Route::get('/available_items', [SupplierItemsController::class, 'availableItems']);
    Route::post('/{id}/approve', [InventoryTransactionController::class, 'approve']);
    Route::post('/{id}/reject', [InventoryTransactionController::class, 'reject']);

});
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
