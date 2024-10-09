<?php

namespace App\Models;

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
        'diagnosis',
        'prescription',
        'follow_up_date',
        'physician_id',
        'physician_name'
    ];
}
