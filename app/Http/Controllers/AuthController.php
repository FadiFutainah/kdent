<?php
namespace App\Http\Controllers;
use App\Services\AuthServices;
use Illuminate\Http\Request;

class AuthController {
    protected $service;
    public function __construct(AuthServices $service) { $this->service = $service; }

    // المسار: /register-patient
    public function register(Request $request) {
        $this->service->registerPatient($request->all());
        return response()->json(['message' => 'تم إرسال كود التحقق بنجاح']);
    }

    // *** المسار الجديد: /verify-otp ***
    public function verify(Request $request) {
        $request->validate([
            'phone_number' => 'required',
            'otp_code'     => 'required'
        ]);

        try {
            $token = $this->service->verifyOtp($request->phone_number, $request->otp_code);
            return response()->json([
                'message' => 'تم تفعيل الحساب بنجاح',
                'token'   => $token
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
    public function resendOtp(Request $request)
{
    $request->validate([
        'phone_number' => 'required'
    ]);

    return response()->json([
        'message' => $this->service->resendOtp($request->phone)
    ]);
}

    // المسار: /login
    public function login(Request $request) {
        try {
           // $token = $this->service->login($request->login_field, $request->password, $request->role);
           $result = $this->service->login($request->all());
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }
     // 3. تسجيل الخروج
    public function logout(Request $request)
    {
        // حذف التوكن الحالي الذي تم استخدامه للمصادقة
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }
}