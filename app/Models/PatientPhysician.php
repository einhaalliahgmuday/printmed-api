<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PatientPhysician extends Pivot
{
    use HasFactory;

    protected $fillable = [
        'physician_id',
        'patient_id'
    ];
}
