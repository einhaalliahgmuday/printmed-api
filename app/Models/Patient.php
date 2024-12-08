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
            ->addOptionalTextField('barangay_code')
            ->addOptionalTextField('city')
            ->addOptionalTextField('city_code')
            ->addOptionalTextField('province')
            ->addOptionalTextField('province_code')
            ->addOptionalTextField('region')
            ->addOptionalTextField('region_code')
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
        'barangay_code',
        'city',
        'city_code',
        'province',
        'province_code',
        'region',
        'region_code',
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
        'qr_status',
        'vital_signs',

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

        return sprintf('P%05d-%d', $increment, $year);
    }

    public function getFullNameAttribute() {
        return $this->getFullName($this->first_name, $this->middle_name, $this->last_name, $this->suffix);
    }

    public function getAddressAttribute()
    {
        $address = "";

        if ($this->house_number) {
            $address .= "{$this->house_number}";
        }
        if ($this->street) {
            $address .= ", {$this->street}";
        }
        if ($this->barangay) {
            $address .= ", {$this->barangay}";
        }
        if ($this->city) {
            $address .= ", {$this->city}";
        }
        if ($this->province) {
            $address .= ", {$this->province}";
        }
        if ($this->postal_code) {
            $address .= ", {$this->postal_code}";
        }

        $address = trim($address);

        return $address;
    }

    public function getAgeAttribute()
    {
        $age = Carbon::parse($this->birthdate)->age;
        return $age;
    }

    public function getLastVisitDate(int $departmentId) 
    {
        $latestConsultationByDepartment = $this->consultations()->select('created_at')
                                                ->where('department_id', $departmentId)
                                                ->latest()
                                                ->first();

        return $latestConsultationByDepartment ?-> created_at;
    }

    public function getQrStatusAttribute()
    {
        $latestQr = PatientQr::where('patient_id', $this->id)->latest()->first();
        $qrCount = PatientQr::where('patient_id', $this->id)->count();

        $qrStatus = [];

        if ($latestQr)
        {
            $qrStatus['status'] = "Active";
            if ($latestQr->is_deactivated === 1) {
                $qrStatus['status'] = "Deactivated";
                $qrStatus['date_deactivated'] = $latestQr->updated_at;
            } else if ($latestQr->created_at < now()->subYear()) {
                $qrStatus['status'] = "Expired";
            }
            $qrStatus['date_issued'] = $latestQr->created_at;
            $qrStatus['issuances_count'] = $qrCount;
        } else {
            $qrStatus['status'] = null;
        }

        return $qrStatus;;
    }

    public function getVitalSignsAttribute()
    {
        return $this->vitalSigns()->where('created_at', '>', now()->startOfDay())->first();
    }

    public function isNewInDepartment(int $departmentId)
    {
        return !$this->consultations()->where('department_id', $departmentId)
                                    ->exists();
    }

    public function getPhysician(int $departmentId) 
    {
        return $this->physicians()->where('users.department_id', $departmentId)
                                ->where('is_locked', 0)
                                ->orderByDesc('patient_physician.created_at')
                                ->first();
    }

    public function getFollowUpDate(int $departmentId) 
    {
        $latestConsultationByDepartment = $this->consultations()->select('follow_up_date')
                                                ->where('department_id', $departmentId)
                                                ->latest()
                                                ->first();

        return $latestConsultationByDepartment ?-> follow_up_date;
    }

    public function vitalSigns()
    {
        return $this->hasMany(VitalSigns::class, 'patient_id');
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class, 'patient_id')
                    ->select('id', 'chief_complaint', 'primary_diagnosis', 'created_at', 'updated_at');
    }

    public function physicians()
    {
        return $this->belongsToMany(User::class, 'patient_physician', 'patient_id', 'physician_id')
                    ->select('users.id', 'role', 'personnel_number', 'users.full_name', 'users.first_name', 'users.middle_name', 'users.last_name', 'users.suffix', 'users.sex', 'users.department_id');
    }
}