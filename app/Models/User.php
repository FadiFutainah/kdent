<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // لتفعيل مكتبة سباتي

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens,HasFactory, Notifiable,HasRoles;
protected $guard_name = 'api';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',           // للموظفين (طبيب، محاسب...)
        'phone_number',    // للمرضى
        'password',
        'otp_code',        // كود التحقق المرسل للواتساب
        'otp_expires_at',  // تاريخ انتهاء الكود
        'is_verified',     // حالة تفعيل حساب المريض 
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'otp_expires_at' => 'datetime',
        'is_verified' => 'boolean',
        ];
    }
 public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }
public function patient()
    {
        return $this->hasOne(patient::class);
    }
    public function notifications()
{
    return $this->hasMany(Notification::class);
}
protected $casts = [
    'is_verified' => 'boolean',
];
}
