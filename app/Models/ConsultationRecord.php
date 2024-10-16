<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'height',
        'weight',
        'blood_pressure',
        'temperature',
        'chief_complaint',
        'history_of_present_illness',
        'family_hx',
        'medical_hx',
        'pediatrics_h',
        'pediatrics_e',
        'pediatrics_a',
        'pediatrics_d',
        'primary_diagnosis',
        'diagnosis',
        'prescription',
        'follow_up_date',
        'physician_id',
        'physician_name',
        'department'
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d');
    }

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
