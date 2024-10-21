<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $appends = [
        // $this->consultationRecords()
        // 'payments',
        // 'queue',
        // 'physicians',
        // 'secretaries'
    ];

    public function consultationRecords()
    {
        return $this->hasMany(ConsultationRecord::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function queue()
    {
        return $this->hasOne(Queue::class);
    }

    public function physicians()
    {
        return $this->hasMany(User::class)->where('role', 'physician');
    }

    public function secretaries()
    {
        return $this->hasMany(User::class)->where('role', 'secretary');
    }
}
