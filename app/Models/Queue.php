<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'total',
        'current',
        'waiting',
        'completed'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
