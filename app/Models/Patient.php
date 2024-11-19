<?php

namespace App\Models;

use App\Traits\CommonMethodsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

class Patient extends Model implements CipherSweetEncrypted
{
    use HasFactory, CommonMethodsTrait;
    use UsesCipherSweet;

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addField('patient_number')
            ->addBlindIndex('patient_number', new BlindIndex('patient_number_index'))
            ->addField('full_name')
            ->addBlindIndex('full_name', new BlindIndex('full_name_index'))
            ->addField('first_name')
            ->addBlindIndex('first_name', new BlindIndex('first_name_index'))
            ->addOptionalTextField('middle_name')
            ->addField('last_name')
            ->addBlindIndex('last_name', new BlindIndex('last_name_index'))
            ->addOptionalTextField('suffix')
            ->addOptionalTextField('birthdate')
            ->addBlindIndex('birthdate', new BlindIndex('birthdate_index'))
            ->addOptionalTextField('birthplace')
            ->addOptionalTextField('sex')
            ->addBlindIndex('sex', new BlindIndex('sex_index'))
            ->addOptionalTextField('house_number')
            ->addOptionalTextField('street')
            ->addOptionalTextField('barangay')
            ->addOptionalTextField('city')
            ->addOptionalTextField('province')
            ->addOptionalTextField('postal_code')
            ->addOptionalTextField('civil_status')
            ->addOptionalTextField('religion')
            ->addOptionalTextField('phone_number')
            ->addOptionalTextField('email')
            ->addOptionalTextField('photo');
    }

    protected $fillable = [
        'patient_number',
        'full_name',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birthdate',
        'birthplace',
        'sex',
        'house_number',
        'street',
        'barangay',
        'city',
        'province',
        'postal_code',
        'civil_status',
        'religion',
        'phone_number',
        'email',
        'photo'
    ];

    protected $appends = [
        'address',
        'age',
        'last_visit',
        'follow_up_date',
        // 'latest_prescription',
        // 'qr_status',
        // 'physicians'
        
    ];

    // generates unique patient number
    public static function generatePatientNumber()
    {
        $year = date('Y');
        $lastPatient = self::select('patient_number')
                        ->latest()
                        ->first();

        $increment = 1;

        if ($lastPatient) 
        {
            if (str_contains($lastPatient->patient_number, $year))
            {
                $increment = (int) substr($lastPatient->patient_number,5) + 1;
            }
        }

        return sprintf('%s-%05d', $year, $increment);
    }

    public function getAddressAttribute()
    {
        $address = "";

        if ($this->house_number) {
            $address .= "{$this->house_number}, ";
        }
        if ($this->street) {
            $address .= "{$this->street}, ";
        }
        if ($this->barangay) {
            $address .= "{$this->barangay}, ";
        }
        if ($this->city) {
            $address .= "{$this->city}, ";
        }
        if ($this->province) {
            $address .= "{$this->province}, ";
        }
        if ($this->postal_code) {
            $address .= $this->postal_code;
        }

        $address = trim($address);

        return $address;
    }

    public function getAgeAttribute()
    {
        $birthdate = Carbon::parse($this->birthdate)->age;
        return $birthdate;
    }

    public function getLastVisitAttribute()
    {
        $latestConsultationRecord = $this->consultationRecords()->where('patient_id', $this->id)->latest('updated_at')->first();
        
        return  $latestConsultationRecord ? $latestConsultationRecord->updated_at->toDateString() : $this->created_at->toDateString();
    }

    public function getFollowUpDateAttribute()
    {
        return $this->consultationRecords()->latest('created_at')->first() ?-> follow_up_date;
    }

    public function getLatestPrescriptionAttribute()
    {
        $latestConsultationRecord = $this->consultationRecords()->latest('created_at')->first();

        if ($latestConsultationRecord && (Carbon::parse($latestConsultationRecord->created_at)->format('Y-m-d') === now()->format('Y-m-d'))) {
            return $latestConsultationRecord->prescription;
        }

        return null;
    }

    public function getQrStatus()
    {
        $latestQr = PatientQr::where('patient_id', $this->id)->latest()->first();
        $qrCount = PatientQr::where('patient_id', $this->id)->count();

        if ($latestQr)
        {
            $qrStatus['is_qr_active'] = $latestQr->isDeactivated;
            $qrStatus['qrs_count'] = $qrCount;

            return $qrStatus;
        }

        return null;
    }

    public function getPhysiciansAttribute()
    {
        return $this->physicians()->get();
    }

    public function consultationRecords()
    {
        return $this->hasMany(Consultation::class, 'patient_id')
                    ->select('id', 'chief_complaint', 'primary_diagnosis', 'created_at', 'updated_at');
    }

    public function vitalSigns()
    {
        return $this->hasMany(VitalSigns::class, 'patient_id');
    }

    public function physicians()
    {
        return $this->belongsToMany(User::class, 'patient_physicians', 'patient_id', 'physician_id')
                    ->select('users.id', 'role', 'personnel_number', 'users.full_name', 'users.sex', 'department_id', 'license_number');
    }
}