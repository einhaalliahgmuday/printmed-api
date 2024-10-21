<?php

namespace App\Models;

use App\Traits\CommonMethodsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory, CommonMethodsTrait;

    protected $fillable = [
        'patient_number',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birthday',
        'sex',
        'address',
        'civil_status',
        'religion',
        'phone_number'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'consultation_records',
        'physicians'
    ];

    protected $appends = [
        'full_name',
        'last_visit',
        'follow_up_date'
    ];

    public static function generatePatientNumber()
    {
        $year = date('Y');
        $lastPatient = self::select('patient_number')
                        ->where('patient_number', 'like', "%$year-%")
                        ->orderBy('patient_number', 'desc')
                        ->first();
        
        $increment = $lastPatient ? (int) substr($lastPatient->patient_id,5) + 1 : 1;

        return sprintf('%s-%05d', $year, $increment);
    }

    public function getFullNameAttribute()
    {
        return $this->getFullName($this->first_name, $this->middle_name, $this->last_name, $this->suffix);
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
        return $this->hasMany(ConsultationRecord::class, 'patient_id');
    }

    public function physicians()
    {
        return $this->belongsToMany(User::class, 'physician_patients', 'patient_id', 'physician_id');
    }
}