<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientPhysician extends Model
{
    use HasFactory;

    protected $fillable = [
        'physician_id',
        'patient_id'
    ];
}
