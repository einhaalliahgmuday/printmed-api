<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ConsultationRecord extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $auditInclude = [
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
    ];

    protected $fillable = [
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
        'patient_id',
        'physician_id',
        'department_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function physician()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
