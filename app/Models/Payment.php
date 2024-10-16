<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'patient_id',
        'patient_name',
        'amount',
        'method',
        'hmo',
        'status',
        'physician_id',
        'physician_name',
        'department',
        'updated_by_id',
        'updated_by_name',
        'update_count'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
