<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\CommonMethodsTrait;
use Illuminate\Support\Facades\Storage;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

class User extends Authenticatable implements CipherSweetEncrypted
{
    use HasFactory, Notifiable, HasApiTokens;
    use UsesCipherSweet;
    use CommonMethodsTrait;

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addField('role')
            ->addBlindIndex('role', new BlindIndex('role_index'))
            ->addField('personnel_number')
            ->addBlindIndex('personnel_number', new BlindIndex('personnel_number_index'))
            ->addField('full_name')
            ->addBlindIndex('full_name', new BlindIndex('full_name_index'))
            ->addField('first_name')
            ->addBlindIndex('first_name', new BlindIndex('first_name_index'))
            ->addOptionalTextField('middle_name')
            ->addField('last_name')
            ->addBlindIndex('last_name', new BlindIndex('last_name_index'))
            ->addOptionalTextField('suffix')
            ->addField('sex')
            ->addField('birthdate')
            ->addBlindIndex('birthdate', new BlindIndex('birthdate_index'))
            ->addOptionalTextField('signature')
            ->addField('email')
            ->addBlindIndex('email', new BlindIndex('email_index'));
    }

    protected $fillable = [
        'role',
        'personnel_number',
        'full_name',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'sex',
        'birthdate',
        'signature',
        'department_id',
        'email',
        'password',
        'is_locked',
        'failed_login_attempts',
        'restricted_timestamp',
        'email_verified_at'
    ];

    protected $appends = [
        'department_name'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getFullNameAttribute() {
        return $this->getFullName($this->first_name, $this->middle_name, $this->last_name, $this->suffix);
    }

    public function getDepartmentNameAttribute() {
        if ($this->department) {
            return $this->department->name;
        }

        return null;
    }

    public function department()
    {
        if($this->role === 'physician' || $this->role === 'secretary')
        {
            return $this->hasOne(Department::class, 'id', 'department_id')
                        ->select('name');
        }

        return $this->hasOne(Department::class, 'id', 'department_id')->whereNull('id');
    }

    public function patients()
    {
        if($this->role === 'physician')
        {
            return $this->belongsToMany(Patient::class, 'patient_physician', 'physician_id', 'patient_id')
                        ->select('patients.id', 'patient_number', 'patients.full_name', 'patients.birthdate', 'patients.sex', 'patients.created_at');
        }

        return collect(); 
    }
}
