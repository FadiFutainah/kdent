<?php
namespace App\Services;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Treatment_Category;
use App\Models\Treatment_Session;
use App\Models\Treatment_Plan;
use OwenIt\Auditing\Models\Audit;
use Carbon\Carbon;
use Exception;

class AdminService
{
    public function createEmployee($data)
    {
         return DB::transaction(function () use ($data) {

        // $allowedRoles = ['doctor', 'accountant','secretary','storekeeper'];

        // if (!in_array($data['role'], $allowedRoles)) {
        //     throw new \Exception("نوع المستخدم غير صالح");
        // }
          if (
            $data['role'] === 'doctor' &&
            empty($data['specialization_id'])
        ) {
            return [
                'success' => false,
                'message' => 'يجب تحديد الاختصاص للطبيب'
            ];
        }

        // 1️⃣ إنشاء User
        $user = User::create([
            'name' => $data['name'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'is_verified' => true
        ]);

        // 2️⃣ assign role
        $user->assignRole($data['role']);

        // 3️⃣ doctor profile
        if ($data['role'] === 'doctor') {

            Doctor::create([
                'user_id' => $user->id,
                'specialization_id' => $data['specialization_id'],
                'percentage' => $data['percentage'] ?? 0,
                'is_active' => true
            ]);
        }

        return [
            'message' => 'تم إنشاء الموظف بنجاح',
            'user_id' => $user->id,
            'role' => $data['role']
        ];
    });
}
public function getEmployees()
{
    $employees = User::with([
        'roles',
        'doctor.specialization'
    ])
    ->whereHas('roles', function ($q) {
        $q->whereIn('name', [
            'doctor',
            'accountant',
            'secretary',
            'storekeeper'
        ]);
    })
    ->latest()
    ->get();

    return $employees->map(function ($user) {

       return array_filter([
            'id' => $user->id,
            'name' => $user->name,
            'phone_number' => $user->phone_number,
            'email' => $user->email,
            'role' => $user->roles->first()?->name,
            'doctor_info' => $user->doctor ? [
                'doctor_id' => $user->doctor->id,
                'specialization' => $user->doctor->specialization?->name,
                'percentage' => $user->doctor->percentage,
            ] : null,
        ]);
    });
}

    public function deleteUser(int $userId): array
    {
        $user = User::findOrFail($userId);

        if ($user->hasRole('admin')) {
            throw new \Exception('لا يمكن حذف الأدمن');
        }

        DB::transaction(function () use ($user) {
            // 1️⃣ إذا كان طبيب، احذف ملف الطبيب (Soft Delete)
            if ($user->doctor) {
                $user->doctor->delete();
            }

            // 2️⃣ إذا كان مريض، احذف ملف المريض (Soft Delete)
            if ($user->patient) {
                $user->patient->delete();
            }
            
            // 3️⃣ احذف المستخدم نفسه (Soft Delete)
            $user->delete();
        });

        return [
            'message' => 'User and associated profile deleted successfully'
        ];
    }

    public function restoreUser(int $userId): array
    {
        $user = User::withTrashed()->findOrFail($userId);

        DB::transaction(function () use ($user) {
            // 1️⃣ استعادة المستخدم
            $user->restore();

            // 2️⃣ استعادة سجل الطبيب إن وجد
            $doctor = Doctor::withTrashed()->where('user_id', $user->id)->first();
            if ($doctor) {
                $doctor->restore();
            }

            // 3️⃣ استعادة سجل المريض إن وجد
            $patient = Patient::withTrashed()->where('user_id', $user->id)->first();
            if ($patient) {
                $patient->restore();
            }
        });

        return [
            'message' => 'User and associated profile restored successfully'
        ];
    }

    public function getDeletedUsers()
    {
        return User::onlyTrashed()
            ->with('roles')
            ->latest('deleted_at')
            ->get()
            ->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'role' => $user->roles->first()?->name,
                    'deleted_at' => $user->deleted_at,
                ];
            });
    }


    public function updateDoctorInfo(int $doctorId, array $data)
    {
        $doctor = Doctor::with('specialization')
            ->findOrFail($doctorId);

        $doctor->update(array_filter([
            'percentage' => $data['percentage'] ?? null,
            'specialization_id' => $data['specialization_id'] ?? null,
        ], fn ($value) => !is_null($value)));

        $doctor->load('specialization');

        return [
            'doctor_id' => $doctor->id,
            'doctor_name' => $doctor->user?->name,
            'percentage' => $doctor->percentage,
            'specialization_id' => $doctor->specialization_id,
            'specialization' => $doctor->specialization?->name,
        ];
    }

    public function getTreatmentCategories()
    {
        $rate = app(ExchangeRateService::class)
            ->getCurrentUsdToSypRate()
            ->rate;

        return Treatment_Category::all()->map(function ($category) use ($rate) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'price_usd' => (float)$category->price_usd,
                'price_syp' => round($category->price_usd * $rate),
            ];
        });
    }

    public function createTreatmentCategory(array $data)
    {
        return Treatment_Category::create([
            'name' => $data['name'],
            'price_usd' => $data['price_usd'],
        ]);
    }

    public function updateTreatmentCategory(int $id, array $data)
    {
        $category = Treatment_Category::findOrFail($id);

        $category->update(array_filter([
            'name' => $data['name'] ?? null,
            'price_usd' => $data['price_usd'] ?? null,
        ], fn ($value) => !is_null($value)));

        return $category;
    }

    public function deleteTreatmentCategory(int $id)
    {
        $category = Treatment_Category::findOrFail($id);

        $category->delete();

        return [
            'message' => 'Treatment category deleted successfully.'
        ];
    }
    public function getDoctorsPerformance(?string $from = null, ?string $to = null)
    {
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        return Treatment_Session::with([
            'planItem.plan.doctor.user',
            'planItem.plan.doctor.specialization'
        ])
            ->where('status', 'completed')
            ->whereBetween('session_date', [$fromDate, $toDate])
            ->get()
            ->groupBy(function ($session) {
                return optional($session->planItem?->plan?->doctor)->id;
            })
            ->filter()
            ->map(function ($sessions) {

                $doctor = $sessions->first()->planItem->plan->doctor;

                return [
                    'doctor_name' => $doctor->user->name,
                    'specialization' => $doctor->specialization->name,
                    'completed_sessions' => $sessions->count(),
                ];
            })
            ->sortByDesc('completed_sessions')
            ->values();
    }

    public function getPatientsCount(string $from, string $to): array
    {
        $count = Patient::whereBetween('created_at', [
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay(),
        ])->count();

        return [
            'total_patients' => $count,
        ];
    }

    public function getCompletedTreatmentPlansCount(): array
    {
        $count = Treatment_Plan::where('status', 'completed')->count();

        return [
            'completed_treatment_plans' => $count,
        ];
    }


    public function getAuditLogs(array $filters = [])
    {
        $query = Audit::query()
            ->with(['user.roles'])
            ->latest('created_at')
            ->latest('id');
            

        /*
        |--------------------------------------------------------------------------
        | Role filter (رول منفذ العملية)
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['role'])) {
            $query->whereHas('user.roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
                // إذا الـ role عندك عمود نصي بدل علاقة، استخدم:
                // $q->where('role', $filters['role']);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Performer name filter (اسم منفذ العملية)
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['user_name'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['user_name']}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Performer phone filter (رقم هاتف منفذ العملية)
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['user_phone'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('phone_number', 'like', "%{$filters['user_phone']}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Event filter
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['event'])) {

        $event = match (mb_strtolower(trim($filters['event']))) {
            'created', 'انشاء' => 'created',
            'updated', 'تعديل' => 'updated',
            'deleted', 'حذف' => 'deleted',
            'restored', 'استعادة' => 'restored',
            default => $filters['event'],
        };

        $query->where('event', $event);
    }

        /*
        |--------------------------------------------------------------------------
        | Model filter
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['auditable_type'])) {

        $modelClass = $this->resolveAuditableClass(
            $filters['auditable_type']
        );

        if ($modelClass) {
            $query->where('auditable_type', $modelClass);
        }
    }

        /*
        |--------------------------------------------------------------------------
        | IP filter
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['ip_address'])) {
            $query->where('ip_address', 'like', "%{$filters['ip_address']}%");
        }

        /*
        |--------------------------------------------------------------------------
        | Date filters
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['from_date'])) {
            $query->whereDate(
                'created_at',
                '>=',
                $filters['from_date']
            );
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate(
                'created_at',
                '<=',
                $filters['to_date']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = min(
            max((int) ($filters['per_page'] ?? 20), 1),
            100
        );

        $logs = $query->paginate($perPage);

        /*
        |--------------------------------------------------------------------------
        | Format response for React
        |--------------------------------------------------------------------------
        */

        $logs->getCollection()->transform(function ($audit) {

            return [
                'audit_id' => $audit->id,

                'user' => $audit->user
                    ? [
                        'user_id' => $audit->user->id,
                        'name' => $audit->user->name,
                        'email' => $audit->user->email,
                        'phone_number' => $audit->user->phone_number,
                        'role' => $audit->user->roles->first()?->name,                    ]
                    : null,

                'event' => $audit->event,

                'event_label' => $this->getAuditEventLabel(
                    $audit->event
                ),

                'auditable' => [
                    'type' => $this->getAuditableName(
                        $audit->auditable_type
                    ),
                    'auditable_id' => $audit->auditable_id,
                ],

                'old_values' => $audit->old_values,

                'new_values' => $audit->new_values,

                'url' => $audit->url,

                'ip_address' => $audit->ip_address,

                'created_at' => $audit->created_at,
            ];
        });

        return $logs;
    }

    private function getAuditEventLabel(string $event): string
    {
        return match ($event) {

            'created' => 'انشاء',

            'updated' => 'تعديل',

            'deleted' => 'حذف',

            'restored' => 'استعادة',

            default => $event,
        };
    }
    private function getAuditableName(?string $type): ?string
    {
        if (!$type) {
            return null;
        }

        return match (class_basename($type)) {

            'User' => 'مستخدم',

            'Patient' => 'مريض',

            'Doctor' => 'طبيب',

            'Doctor_Schedule' => 'أوقات طبيب',

            'Doctor_Payment' => 'دفعة طبيب',

            'Doctor_Earning' => 'أرباح طبيب',

            'Treatment_Plan' => 'خطة علاج',

            'Treatment_Session' => 'جلسة علاج',

            'Treatment_Category' => 'تصنيف علاجي',

            'Invoice' => 'فاتورة',

            'Invoice_Item' => 'عنصر فاتورة',

            'Payment' => 'دفعات فواتير المرضى',

            'Item' => 'مادة',

            'Inventory' => 'مخزون',

            'InventoryTransaction' => 'حركة مخزون',

            'InventoryAudit' => 'جرد مخزون',

            'AuditItem' => 'عنصر جرد',

            'Supplier' => 'مورد',

            'SupplierItem' => 'عنصر مورد',

            'MaterialRequest' => 'طلب مواد',

            'MaterialRequestItem' => 'عنصر طلب مواد',

            'Disposal' => 'إتلاف',

            'DisposalItem' => 'عنصر إتلاف',

            'Exchange_Rate' => 'سعر صرف',

            'SalaryPayment' => 'راتب موظف',

            'SalaryAdjustment' => 'تعديل راتب',

            'Medical_Report' => 'تقرير طبي',

            'ToothTreatment' => 'خارطة سنية',

            'Plan_Item' => 'عنصر خطة',

            'Notification' => 'اشعار',

            'Appointment' => 'موعد',

            'Specialization' => 'اختصاص',

            default => class_basename($type),
        };
    }

    private function resolveAuditableClass(string $key): ?string
    {
        $key = trim($key);

        return match (mb_strtolower($key)) {

            'user', 'مستخدم' => User::class,

            'patient', 'مريض' => Patient::class,

            'doctor', 'طبيب' => Doctor::class,

            'doctor_schedule', 'doctor schedule', 'أوقات طبيب'
                => \App\Models\Doctor_Schedule::class,

            'doctor_payment', 'doctor payment', 'دفعة طبيب'
                => \App\Models\Doctor_Payment::class,

            'doctor_earning', 'doctor earning', 'أرباح طبيب'
                => \App\Models\Doctor_Earning::class,

            'treatment_plan', 'treatment plan', 'خطة علاج'
                => Treatment_Plan::class,

            'treatment_session', 'treatment session', 'جلسة علاج'
                => Treatment_Session::class,

            'treatment_category', 'treatment category', 'تصنيف علاجي'
                => Treatment_Category::class,

            'invoice', 'فاتورة'
                => \App\Models\Invoice::class,

            'invoice_item', 'invoice item', 'عنصر فاتورة'
                => \App\Models\Invoice_Item::class,

            'payment', 'دفعات فواتير المرضى'
                => \App\Models\Payment::class,

            'item', 'مادة'
                => \App\Models\Item::class,

            'inventory', 'مخزون'
                => \App\Models\Inventory::class,

            'inventory_transaction', 'inventory transaction', 'حركة مخزون'
                => \App\Models\InventoryTransaction::class,

            'inventory_audit', 'inventory audit', 'جرد مخزون'
                => \App\Models\InventoryAudit::class,

            'audit_item', 'audit item', 'عنصر جرد'
                => \App\Models\AuditItem::class,

            'supplier', 'مورد'
                => \App\Models\Supplier::class,

            'supplier_item', 'supplier item', 'عنصر مورد'
                => \App\Models\SupplierItem::class,

            'material_request', 'material request', 'طلب مواد'
                => \App\Models\MaterialRequest::class,

            'material_request_item', 'material request item', 'عنصر طلب مواد'
                => \App\Models\MaterialRequestItem::class,

            'disposal', 'إتلاف'
                => \App\Models\Disposal::class,

            'disposal_item', 'disposal item', 'عنصر إتلاف'
                => \App\Models\DisposalItem::class,

            'exchange_rate', 'exchange rate', 'سعر صرف'
                => \App\Models\Exchange_Rate::class,

            'salary_payment', 'salary payment', 'راتب موظف'
                => \App\Models\SalaryPayment::class,

            'salary_adjustment', 'salary adjustment', 'تعديل راتب'
                => \App\Models\SalaryAdjustment::class,

            'medical_report', 'medical report', 'تقرير طبي'
                => \App\Models\Medical_Report::class,

            'tooth_treatment', 'tooth treatment', 'خارطة سنية'
                => \App\Models\ToothTreatment::class,

            'plan_item', 'plan item', 'عنصر خطة'
                => \App\Models\Plan_Item::class,

            'notification', 'اشعار'
                => \App\Models\Notification::class,

            'appointment', 'موعد'
                => \App\Models\Appointment::class,

            'specialization', 'اختصاص'
                => \App\Models\Specialization::class,

            default => null,
        };
    }




}
