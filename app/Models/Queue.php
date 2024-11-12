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
    
    protected $appends = [
        'department_name'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function getDepartmentNameAttribute() {
        return $this->department()->first()->name;
    }

    public function department()
    {
        return $this->hasOne(Department::class, 'id', 'department_id')->select('name');
    }
}
