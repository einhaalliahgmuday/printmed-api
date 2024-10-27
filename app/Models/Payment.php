<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

class Payment extends Model implements CipherSweetEncrypted
{
    use HasFactory;
    use UsesCipherSweet;

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addField('amount')
            ->addOptionalTextField('hmo');
    }
    
    protected $fillable = [
        'amount',
        'method',
        'hmo',
        'is_paid',
        'updated_by_id',
        'consultation_id',
        'patient_id',
        'physician_id',
        'department_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $appends = [
        'date',
        'time',
        'patient_name',
        'physician_name',
        'updated_by_name',
        'department_name'
    ];

    public function getDateAttribute() 
    {
        return $this->created_at->toDateString();
    }

    public function getTimeAttribute() 
    {
        return $this->created_at->format('h:i A');
    }

    public function getPatientNameAttribute()
    {
        return $this->patient()->first()->full_name; 
    }

    public function getPhysicianNameAttribute()
    {
        return $this->physician()->first()->full_name; 
    }

    public function getUpdatedByNameAttribute()
    {
        return $this->updatedBy()->first() ?-> full_name; 
    }

    public function getDepartmentNameAttribute()
    {
        return $this->department()->first()->name;
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class)
                    ->select('id', 'patient_number', 'full_name', 'birthdate', 'sex');
    }

    public function physician()
    {
        return $this->belongsTo(User::class)
                    ->select('id', 'personnel_number', 'full_name', 'sex', 'department_id', 'license_number');
    }

    public function department()
    {
        return $this->belongsTo(Department::class); 
    }

    public function updatedBy()
    {
        return $this->hasOne(User::class, 'id', 'updated_by_id')
                    ->select('id', 'role', 'personnel_number', 'full_name', 'sex', 'department_id', 'license_number');
    }
}
