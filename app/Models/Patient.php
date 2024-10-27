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
            ->addOptionalTextField('address')
            ->addOptionalTextField('civil_status')
            ->addOptionalTextField('religion')
            ->addOptionalTextField('phone_number');
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
        'address',
        'civil_status',
        'religion',
        'phone_number'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $appends = [
        'age',
        'last_visit',
        'follow_up_date'
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
        return $this->consultationRecords()->where('patient_id', $this->id)->latest('updated_at')->first() ?-> follow_up_date;
    }

    public function consultationRecords()
    {
        return $this->hasMany(Consultation::class, 'patient_id')
                    ->select('id', 'chief_complaint', 'primary_diagnosis', 'diagnosis', 'follow_up_date', 'updated_at');
    }

    public function physicians()
    {
        return $this->belongsToMany(User::class, 'patient_physicians', 'patient_id', 'physician_id')
                    ->select('users.id', 'role', 'personnel_number', 'users.full_name', 'users.sex', 'department_id', 'license_number');
    }
}