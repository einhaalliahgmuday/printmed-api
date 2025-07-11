<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    protected $appends = [
        'users_count'
    ];

    public function getUsersCountAttribute() 
    {
        return $this->hasMany(User::class, 'department_id', 'id')->count();
    }
}
