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
            ->addOptionalTextField('blood_pressure')
            ->addOptionalTextField('temperature')
            ->addField('chief_complaint')
            ->addOptionalTextField('present_illness_hx')
            ->addOptionalTextField('family_hx')
            ->addOptionalTextField('medical_hx')
            ->addOptionalTextField('pediatrics_h')
            ->addOptionalTextField('pediatrics_e')
            ->addOptionalTextField('pediatrics_a')
            ->addOptionalTextField('pediatrics_d')
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
        'blood_pressure',
        'chief_complaint',
        'present_illness_hx',
        'family_hx',
        'medical_hx',
        'pediatrics_h',
        'pediatrics_e',
        'pediatrics_a',
        'pediatrics_d',
        'primary_diagnosis',
        'diagnosis',
        'follow_up_date',
        'patient_id',
        'physician_id',
        'department_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $appends = [
        'prescriptions'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function physician()
    {
        return $this->belongsTo(User::class)
                    ->select('id', 'personnel_number', 'full_name', 'sex', 'department_id', 'license_number');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }
}
