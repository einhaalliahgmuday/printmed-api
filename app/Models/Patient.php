<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'full_name',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birthday',
        'sex',
        'address',
        'civil_status',
        'religion',
        'phone_number',
        'last_visit',
        'follow_up_date'
    ];

    public static function generatePatientNumber()
    {
        $year = date('Y');
        $lastPatient = self::select('patient_id')
                        ->where('patient_id', 'like', "%$year-%")
                        ->orderBy('patient_id', 'desc')
                        ->first();
        
        $increment = $lastPatient ? (int) substr($lastPatient->patient_id,5) + 1 : 1;

        return sprintf('%s-%05d', $year, $increment);
    }

    public function physicians()
    {
        return $this->belongsToMany(User::class, 'physician_patients', 'patient_id', 'physician_id');
    }
}