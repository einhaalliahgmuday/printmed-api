<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPasswordNotification;
use App\Traits\CommonMethodsTrait;
use OwenIt\Auditing\Contracts\Auditable;

class User extends Authenticatable implements Auditable
{
    use HasFactory, Notifiable, HasApiTokens, CommonMethodsTrait;
    use \OwenIt\Auditing\Auditable;

    protected $auditEvents = [
        'created',
    ];

    protected $auditInclude = [
        'role',
        'personnel_number',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'sex',
        'birthdate',
        'department_id',
        'license_number',
        'email'
    ];

    protected $fillable = [
        'role',
        'personnel_number',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'sex',
        'birthdate',
        'department_id',
        'license_number',
        'email',
        'password',
        'is_locked',
        'failed_login_attempts',
        'email_verified_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'email_verified_at'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getFullNameAttribute()
    {
        return $this->getFullName($this->first_name, $this->middle_name, $this->last_name, $this->suffix);
    }

    public function patients()
    {
        if($this->role === 'physician')
        {
            return $this->belongsToMany(Patient::class, 'patient_physicians', 'physician_id', 'patient_id');
        }

        return collect(); 
    }

    public function payments()
    {
        if($this->role === 'physician')
        {
            return $this->hasMany(Payment::class, 'physician_id', 'id');
        }

        return collect(); 
    }

    public function sendPasswordResetNotification($token)
    {
        $isNewAccount = $this->password ? true : false;
        $url = url("http://127.0.0.1/reset-password?token={$token}&email={$this->email}");

        $this->notify(new ResetPasswordNotification($isNewAccount, $url, $this->first_name));
    }
}
