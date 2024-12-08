<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

class Consultation extends Model implements CipherSweetEncrypted
{
    use HasFactory;
    use UsesCipherSweet;

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addOptionalTextField('height')
            ->addOptionalTextField('weight')
            ->addOptionalTextField('systolic')
            ->addOptionalTextField('diastolic')
            ->addOptionalTextField('temperature')
            ->addField('chief_complaint')
            ->addOptionalTextField('present_illness_hx')
            ->addOptionalTextField('family_hx')
            ->addOptionalTextField('medical_hx')
            ->addOptionalTextField('birth_maternal_hx')
            ->addOptionalTextField('immunization')
            ->addOptionalTextField('heads')
            ->addOptionalTextField('pertinent_physical_examination')
            ->addOptionalTextField('laboratory_diagnostics_tests')
            ->addOptionalTextField('primary_diagnosis')
            ->addField('diagnosis')
            ->addOptionalTextField('follow_up_date');
    }

    protected $fillable = [
        'height',
        'height_unit',
        'weight',
        'weight_unit',
        'temperature',
        'temperature_unit',
        'systolic',
        'diastolic',
        'chief_complaint',
        'present_illness_hx',
        'family_hx',
        'medical_hx',
        'birth_maternal_hx',
        'immunization',
        'heads',
        'pertinent_physical_examination',
        'laboratory_diagnostics_tests',
        'primary_diagnosis',
        'diagnosis',
        'follow_up_date',
        'patient_id',
        'physician_id',
        'department_id'
    ];

    protected $appends = [
        'physician'
    ];

    public function getPhysicianAttribute() {
        return $this->physician()->first();
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function physician()
    {
        return $this->belongsTo(User::class)
                    ->select('id', 'full_name', 'first_name',  'middle_name', 'last_name', 'suffix');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }
}
