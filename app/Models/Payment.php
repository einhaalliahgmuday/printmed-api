<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'method',
        'hmo',
        'is_paid',
        'updated_by_id',
        'consultation_record_id',
        'patient_id',
        'physician_id',
        'department_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $appends = [
        'date',
        'time',
        'patient_name',
        'physician_name',
        'updated_by_name',
        'department_name'
    ];

    public function getDateAttribute() 
    {
        return $this->created_at->toDateString();
    }

    public function getTimeAttribute() 
    {
        return $this->created_at->format('h:i A');
    }

    public function getPatientNameAttribute()
    {
        return $this->patient()->first()->full_name; 
    }

    public function getPhysicianNameAttribute()
    {
        return $this->physician()->first()->full_name; 
    }

    public function getUpdatedByNameAttribute()
    {
        return $this->updatedBy()->first() ?-> full_name; 
    }

    public function getDepartmentNameAttribute()
    {
        return $this->department()->first()->name;
    }

    public function consultationRecord()
    {
        return $this->belongsTo(ConsultationRecord::class); 
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class); 
    }

    public function physician()
    {
        return $this->belongsTo(User::class); 
    }

    public function department()
    {
        return $this->belongsTo(Department::class); 
    }

    public function updatedBy()
    {
        return $this->hasOne(User::class, 'id', 'updated_by_id'); 
    }
}
