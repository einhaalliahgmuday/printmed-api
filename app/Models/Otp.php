<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'email',
        'code',
        'expires_at'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
