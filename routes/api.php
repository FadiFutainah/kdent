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
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AccountantController;

Route::post('/register', [AuthController::class, 'register']); // للمريض فقط
Route::post('/verify', [AuthController::class, 'verify']);
Route::post('/resendOtp', [AuthController::class, 'resendOtp']);  // للمريض فقط
Route::post('/login', [AuthController::class, 'login']);       // للجميع مع إرسال الرول

Route::middleware('auth:sanctum')->group(function () {
Route::post('/logout', [AuthController::class, 'logout']); });// تسجيل الخروج

Route::middleware('auth:sanctum')->get('/auth/check-token',[AuthController::class, 'checkToken']
);

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
    Route::get('/employees', [AdminController::class, 'employees']);//عرض الموظفين
    Route::get('/audit/{id}', [InventoryTransactionController::class, 'approved']);//موافقة المدير على الجرد + تنفيذ التسوية
    Route::get('/pending_approvals', [InventoryTransactionController::class, 'getPendingAuditsReport']);// عرض الجردات في انتظار الموافقة
    Route::get('/showss/{id}', [InventoryTransactionController::class, 'getAuditResult']);// عرض تفاصيل جرد محدد 
    Route::get('getDisposedItemsHistory', [InventoryTransactionController::class, 'getDisposedItemsHistory']);//عرض جميع المواد التي تم اتلافها للادمن 
    Route::post('/invoices/{id}/approve', [InvoiceController::class, 'approve']);//اعتماد الفاتورة
    Route::get('/Allpatients', [PatientController::class, 'listAllPatients']);

});

Route::middleware(['auth:sanctum', 'role:doctor'])->group(function(){
    Route::post('/addAvailableTime', [DoctorController::class, 'addAvailableTime']);
    Route::post('/updateAvailableTime/{id}', [DoctorController::class, 'updateAvailableTime']);
    Route::delete('/deleteAvailableTime/{id}', [DoctorController::class, 'deleteAvailableTime']);
    Route::get('/myPatients', [DoctorController::class, 'myPatients']);
    Route::get('/todayAppointments', [DoctorController::class, 'todayAppointments']);
    Route::get('/upcomingAppointments', [DoctorController::class, 'upcomingAppointments']);
    Route::post('/material-requests', [DoctorController::class, 'store']);// طلب مواد من المستودع
    Route::get('/doctor/schedules', [DoctorController::class, 'getMyAvailableTimes']);//عرض الأوقات المتاحة للطبيب

});


/*Route::middleware(['auth:sanctum', 'role:secretary|patient'])->group(function () {

    Route::get('/doctors/{doctorId}/available-slots', [AppointmentController::class, 'availableSlotsByDoctor']);
});*/

Route::middleware(['auth:sanctum', 'role:secretary'])->group(function () {
    Route::get('/secretary/doctors/all', [AppointmentController::class, 'listAllDoctorsForSecretary']);
    // لازم get 
    Route::post('/secretary/doctors', [AppointmentController::class, 'listDoctorsBySpecialization']); 
    Route::post('/secretary/doctors/{doctorId}/available-slots', [AppointmentController::class, 'availableSlotsByDoctor']);

    Route::get('/secretary/doctors/{doctorId}/available-slots7', [AppointmentController::class, 'availableSlotsByDoctor7Days']);//للحذف
    Route::get('/secretary/doctors/{doctorId}/patients', [SecretaryController::class, 'doctorPatients']);
    Route::get('/secretary/doctors/{doctorId}/appointments/today', [SecretaryController::class, 'doctorTodayAppointments']);
    Route::get('/secretary/appointments/scheduled', [AppointmentController::class, 'listScheduledBySecretary']);
    Route::get('/secretary/appointments/confirmed', [AppointmentController::class, 'listConfirmedBySecretary']);
    Route::post('/appointments/secretary', [AppointmentController::class, 'bookBySecretary']);
    Route::post('/appointments/{appointmentId}/confirm', [AppointmentController::class, 'confirmBySecretary']);
    Route::post('/appointments/{appointmentId}/cancel', [AppointmentController::class, 'cancelBySecretary']);
    Route::post('/create-patients', [SecretaryController::class, 'createPatient']);


});

Route::middleware(['auth:sanctum', 'role:doctor|secretary'])->group(function () {
    Route::post('/patients/search', [DoctorController::class, 'searchPatients']);
    Route::get('/doctor/schedules', [AppointmentController::class, 'getAvailableSlots']);
    Route::post('/medical-records/{patientId}', [MedicalRecordController::class, 'update']);

});

Route::middleware(['auth:sanctum', 'role:admin|accountant'])->group(function () {
    Route::post('/doctors/{doctorId}/payments', [DoctorFinanceController::class, 'recordPayment']);
    Route::get('/doctors/{doctorId}/finance/summary', [DoctorFinanceController::class, 'summary']);
    Route::post('/exchange-rates/refresh', [ExchangeRateController::class, 'refresh']);
    Route::get('/reports/status-stats', [InvoiceController::class, 'getStatusStats']);// إحصائيات حالة الفواتير
    Route::get('/reports/revenue-stats', [InvoiceController::class, 'getRevenueStats']);//  لسنة إحصائيات الإيرادات الشهرية
    Route::get('/reports/overdue-invoices', [InvoiceController::class, 'getOverdue']);// إحصائيات الفواتير المتأخرة
    Route::get('/reports/revenue', [InvoiceController::class, 'getRevenue']);// إحصائيات الإيرادات لشهر محدد
    Route::get('/payments/{paymentId}/pdf', [DoctorFinanceController::class, 'downloadPaymentPdf']);
    Route::get('/index', [InvoiceController::class, 'index']);//عرض فواتير المورد
});
Route::middleware(['auth:sanctum', 'role:accountant'])->group(function () {
  
    Route::get('/indexs', [InvoiceController::class, 'indexs']);//عرض فواتير المرضى
   // Route::post('/invoices/{id}/mark-as-paid', [InvoiceController::class, 'markAsPaid']);//وضع علامة مدفوعة على الفاتورة
    Route::get('/invoices/{id}/print', [InvoiceController::class, 'print']);//طباعة الفاتورة
    Route::post('/invoices/{id}/pay', [InvoiceController::class, 'pay']);//دفع الفاتورة
    Route::post('/invoices/{id}/apply-discount', [InvoiceController::class, 'applyDiscount']);//تطبيق الخصم
    Route::get('/invoices/{id}', [InvoiceController::class, 'printReceipt']);//طباعة وصل
    Route::get('/doctor/{doctorId}/plans/dues',[AccountantController::class, 'doctorPlansDues']
);
});


Route::middleware(['auth:sanctum', 'role:doctor|admin|accountant|storekeeper'])->group(function () {
    Route::get('/exchange-rates/current', [ExchangeRateController::class, 'current']);
    Route::get('/exchange-rates/history', [ExchangeRateController::class, 'history']);

});

Route::middleware(['auth:sanctum', 'role:patient|doctor|secretary'])->group(function () {
    Route::get('/patients/{patientId}/plans', [TreatmentPlanController::class, 'myPlans']);
    Route::get('/plans/{planId}', [TreatmentPlanController::class, 'show']);
    Route::get('/items/{itemId}', [TreatmentPlanController::class, 'showItem']);
    Route::get('/sessions/{sessionId}', [TreatmentPlanController::class, 'showSession']);
    Route::get('/medical-reports/{patientId}', [MedicalReportController::class, 'index']);
    Route::get('/medical-reports/{reportId}', [MedicalReportController::class, 'show']);
    Route::get('/medical-records/{patientId}', [MedicalRecordController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:doctor'])->group(function () {
    Route::get('/my/finance/summary', [DoctorFinanceController::class, 'mySummary']);
    Route::post('/treatment-plans', [TreatmentPlanController::class, 'store']);
    Route::post('/treatment-plans/{planId}', [TreatmentPlanController::class, 'update']);
    Route::post('/treatment-plans/{planId}/items', [TreatmentPlanController::class, 'addItem']);
    Route::post('/treatment-plans/{planId}/items/{itemId}', [TreatmentPlanController::class, 'updateItem']);
    Route::delete('/treatment-plans/{planId}/items/{itemId}', [TreatmentPlanController::class, 'deleteItem']);
    Route::post('/plan-items/{itemId}/treatment-sessions', [TreatmentSessionController::class, 'store']);
    Route::post('/treatment-sessions/{sessionId}', [TreatmentSessionController::class, 'update']);
    Route::post('/treatment-sessions/{sessionId}/complete', [TreatmentSessionController::class, 'complete']);
    Route::post('/consume', [InventoryTransactionController::class, 'storee']);//طلب مواد من المستودع
    Route::get('/available', [InventoryTransactionController::class, 'getAvailableItemsForDoctor']);//عرض المواد المتاحة للطبيب
    
    Route::post('/treatment-sessions/{sessionId}/complete', [TreatmentSessionController::class, 'complete']);
    Route::post('/medical-reports', [MedicalReportController::class, 'store']);
   // Route::post('/medical-records/{patientId}', [MedicalRecordController::class, 'update']);
    Route::get('/doctor/plans/dues', [DoctorFinanceController::class, 'doctorPlansDues']);

});


Route::middleware(['auth:sanctum', 'role:storekeeper'])->group(function () {
Route::post('/suppliers', [SupplierItemsController::class, 'store']);// إضافة مورد جديد
Route::post('/purchase', [InventoryTransactionController::class, 'purchase']);// تسجيل عملية شراء مواد من مورد
//Route::post('/consume', [InventoryTransactionController::class, 'consume']);
//Route::post('/returnItems', [InventoryTransactionController::class, 'returnItems']);// 
Route::post('/storeitems', [SupplierItemsController::class, 'stores']);// تثبيت مواد في النظام
Route::get('/available_items', [SupplierItemsController::class, 'availableItems']);//عرض المواد
Route::post('/{id}/approve', [InventoryTransactionController::class, 'approveRequest']);// موافقة على طلب مواد
Route::post('/audit', [InventoryTransactionController::class, 'audit']);// إنشاء جرد جديد
Route::get('/shows', [InventoryTransactionController::class, 'shows']);// عرض كل الجردات
Route::get('/showss', [InventoryTransactionController::class, 'showss']);// عرض تفاصيل جرد محدد      
Route::post('/audits/{id}/items', [InventoryTransactionController::class, 'addItem']);// إضافة مادة إلى جرد
Route::get('/audits/{id}/complete', [InventoryTransactionController::class, 'complete']);// انهاء الجرد
Route::get('/material-requests/pending', [InventoryTransactionController::class, 'getPendingDoctorRequests']);// عرض طلبات المواد القادمة من الأطباء بانتظار الموافقة
Route::get('/material-requests/{id}/details', [InventoryTransactionController::class, 'getDoctorRequestDetails']);// عرض تفاصيل طلب محدد مع فحص FIFO
// روابط الإتلاف الخاصة بأمينة المستودع
Route::post('/disposals/manual-immediate', [InventoryTransactionController::class, 'storeManualImmediate']); // زر إتلاف فوري للمواد المكسورة/التالفة
Route::get('/disposals/pending', [InventoryTransactionController::class, 'getPendingDisposals']);          // عرض طلبات الإتلاف "التلقائية" القادمة من الجوب ليلاً بانتظار تأكيدها
Route::post('/disposals/{id}/approve', [InventoryTransactionController::class, 'approve']);                // زر تأكيد وإتلاف الطلبات التلقائية بعد فحص الرف
Route::get('/inventory/by-item/{item_id}', [InventoryTransactionController::class, 'getByItem']);//الحصول على الدفعات لمادة محددة 
Route::get('/showss/{id}', [InventoryTransactionController::class, 'getAuditResult']);// عرض تفاصيل جرد محدد 
Route::post('/reason/{id}/{item_id}', [InventoryTransactionController::class, 'updateVarianceReason']);//اضافة سبب للنقص 
Route::get('/getExpiredItems', [InventoryTransactionController::class, 'getExpiredItems']);//عرض جميع المواد منتهية الصلاحية قبل اتلافها 
Route::get('/getLowStockItems', [InventoryTransactionController::class, 'getLowStockItems']);//عرض المواد التي وصلت إلى حدها الأدنى من المخزون
Route::get('/getAllSuppliers', [SupplierItemsController::class, 'getAllSuppliers']);//عرض جميع الموردين
Route::put('/suppliers/{id}', [SupplierItemsController::class, 'update']);//تعديل مورد


});


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'update']);
    Route::get('/reports/{reportId}/pdf',[MedicalReportController::class, 'downloadPdf']);


});

