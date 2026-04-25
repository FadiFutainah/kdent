<?php
namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Patient;
use Exception;

class AuthServices {
    protected $repo;
    
    public function __construct(UserRepository $repo) { 
        $this->repo = $repo; 
    }

    // 1. تابع إرسال الواتساب
    private function sendWhatsApp($phone, $otp) {
        $instanceId = config('services.ultramsg.instance');
        $token = config('services.ultramsg.token');
        
        Http::post("https://api.ultramsg.com/$instanceId/messages/chat", [
            'token' => $token,
            'to'    => $phone,
            'body'  => "كود التحقق الخاص بك في تطبيق Karam Dent هو: $otp",
        ]);
    }

    // 2. تسجيل المريض وإرسال الكود
    public function registerPatient($data) {
        $user = User::create([
            'name' => $data['name'],
            'phone_number' => $data['phone_number'],
            'password' => bcrypt($data['password']),
            'is_verified' => false
        ]);
        $user->assignRole('patient');
 // 2️⃣ إنشاء patient مباشرة
        patient::create([
            'user_id' => $user->id,
            'file_open_date' => now(),
            // باقي الحقول فاضية بالبداية
            'gender' => null,
            'address' => null,
            'occupation' => null,
        ]);
        $otp = rand(1000, 9999); 
        $user->update([
            'otp_code' => $otp, 
            'otp_expires_at' => now()->addMinutes(15),
            'last_otp_sent_at' => now(),
        ]);
        
        $this->sendWhatsApp($user->phone_number, $otp);
        return $user;
    }

    // 3. تابع التحقق من OTP
    public function verifyOtp($phone, $code) {
        $user = $this->repo->findByIdentifier($phone);

        if (!$user || $user->otp_code != $code) {
            throw new Exception("كود التحقق غير صحيح.");
        }

        if (now()->isAfter($user->otp_expires_at)) {
            throw new Exception("انتهت صلاحية الكود، اطلب كوداً جديداً.");
        }

        $user->update([
            'is_verified' => true,
            'otp_code' => null,
            'otp_expires_at' => null
        ]);

        return $user->createToken('auth_token')->plainTextToken;
    }
    public function resendOtp($phone)
{
    $user = $this->repo->findByIdentifier($phone);

    if (!$user) {
        throw new Exception("المستخدم غير موجود");
    }

    // إذا الحساب متفعل أصلاً
    if ($user->is_verified) {
        throw new Exception("الحساب مفعل بالفعل");
    }

    // (اختياري) منع السبام - إذا الكود لسا ما انتهى
    if ($user->otp_expires_at && now()->lt($user->otp_expires_at)) {
        throw new Exception("الكود الحالي ما زال صالح، حاول لاحقاً");
    }
      // 🔥 منع السبام (انتظار 60 ثانية)
    if ($user->last_otp_sent_at && now()->diffInSeconds($user->last_otp_sent_at) < 60) {
        throw new Exception("انتظر دقيقة قبل طلب كود جديد");
    }

    // توليد كود جديد
    $otp = rand(1000, 9999);

    $user->update([
        'otp_code' => $otp,
        'otp_expires_at' => now()->addMinutes(15),
        'last_otp_sent_at' => now()
    ]);

    // إرسال واتساب
    $this->sendWhatsApp($user->phone_number, $otp);

    return "تم إرسال كود تحقق جديد عبر واتساب";
}

    // 4. تسجيل الدخول المعدل (يستقبل مصفوفة $data)
    // public function login($data) {
    //     // استخراج البيانات من المصفوفة القادمة من الـ Controller
       
    //     $identifier = $data['login_field'] ?? null;
    //     $password = $data['password'] ?? null;
    //     $role = $data['role'] ?? null; // هذا الرول الذي اختاره المستخدم من أول واجهة

    //     // التأكد من أن الواجهة أرسلت الرول فعلاً
    //     if (!$role) {
    //         throw new Exception("يجب تحديد نوع الحساب (طبيب، مريض...) من الواجهة.");
    //     }

    //     $user = $this->repo->findByIdentifier($identifier);

    //     // فحص وجود المستخدم وكلمة المرور
    //     if (!$user || !Hash::check($password, $user->password)) {
    //         throw new Exception("بيانات الدخول غير صحيحة");
    //     }

    //     // فحص هل المستخدم يملك الرول الذي اختاره أم لا
    //     // هنا حل مشكلة الـ TypeError لأننا تأكدنا أن $role ليس null
    //     if (!$user->hasRole($role)) {
    //         throw new Exception("هذا الحساب غير مسجل كـ $role");
    //     }

    //     // فحص التفعيل للمرضى فقط
    //     if ($role === 'patient' && !$user->is_verified) {
    //         throw new Exception("يرجى تفعيل الحساب عبر واتساب أولاً.");
    //     }

    //     // إصدار التوكن في حال نجاح كل الفحوصات
    //     return $user->createToken('auth_token')->plainTextToken;
    // }
//     public function login($data)
// {
//     $identifier = $data['login_field'] ?? null;
//     $password = $data['password'] ?? null;

//     $user = $this->repo->findByIdentifier($identifier);

//     if (!$user || !Hash::check($password, $user->password)) {
//         throw new Exception("بيانات الدخول غير صحيحة");
//     }

//     // تحقق تفعيل المريض فقط
//     if ($user->hasRole('patient') && !$user->is_verified) {
//         throw new Exception("يرجى تفعيل الحساب أولاً.");
//     }

//     $token = $user->createToken('auth_token')->plainTextToken;

//     return [
//         'token' => $token,
//         'user' => $user,
//         'roles' => $user->getRoleNames()
//     ];
// }
public function login($data)
{
    $identifier = $data['login_field'] ?? null;
    $password   = $data['password'] ?? null;
    $role       = $data['role'] ?? null; // اختياري

    $user = $this->repo->findByIdentifier($identifier);

    if (!$user || !Hash::check($password, $user->password)) {
        throw new Exception("بيانات الدخول غير صحيحة");
    }

    // إصلاح بيانات قديمة: إذا كان للمستخدم ملف مريض بدون role نربطه تلقائياً.
    if ($user->getRoleNames()->isEmpty() && $user->patient()->exists()) {
        $user->assignRole('patient');
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $user = $user->fresh();
    }

    $actualRole = $user->getRoleNames()->first();
    if (!$actualRole) {
        throw new Exception("هذا الحساب لا يملك أي role. راجع إنشاء الحساب أو الإسناد.");
    }

    // تحقق تفعيل المريض
    if ($user->hasRole('patient') && !$user->is_verified) {
        throw new Exception("يرجى تفعيل الحساب أولاً.");
    }

    // 🎯 التحقق من الرول (اختياري)
    if ($role && !$user->hasRole($role)) {
        throw new Exception("هذا الحساب ليس {$role}، الدور الصحيح هو {$actualRole}");
    }

    // ✅ دخول طبيعي
    $token = $user->createToken('auth_token')->plainTextToken;

    return [
        'token'   => $token,
        'role'    => $actualRole
    ];
}
}