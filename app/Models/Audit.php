<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;
use App\Models\User;
use App\Models\Patient;
use App\Models\ConsultationRecord;
use App\Models\Payment;
use ParagonIE\CipherSweet\BlindIndex;

class Audit extends Model implements CipherSweetEncrypted
{
    use HasFactory;
    use UsesCipherSweet;

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addOptionalTextField('auditable_type')
            ->addBlindIndex('auditable_type', new BlindIndex('auditable_type_index'))
            ->addOptionalTextField('new_values')
            ->addOptionalTextField('old_values')
            ->addOptionalTextField('url')
            ->addOptionalTextField('ip_address')
            ->addOptionalTextField('user_agent');
    }

    protected $guarded = [
        'id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'old_values'   => 'json',
        'new_values'   => 'json'
    ];

    public function getUserAttribute()
    {
        return User::find($this->user_id);
    }

    public function getAuditableAttribute()
    {
        $auditableClass = class_basename($this->auditable_type);

        switch ($auditableClass) 
        {
            case 'User':
                return User::find($this->user_id);
            case 'Patient':
                return Patient::find($this->user_id);
            case 'Consultation':
                return Consultation::find($this->user_id);
            case 'Payment':
                return Payment::find($this->user_id);
        }
    }
}
