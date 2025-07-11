<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

class PatientQr extends Model implements CipherSweetEncrypted
{
    use HasFactory;
    use UsesCipherSweet;

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addField('uuid')
            ->addBlindIndex('uuid', new BlindIndex('uuid_index'));
    }

    protected $fillable = [
        'uuid',
        'is_deactivated',
        'patient_id'
    ];

    function patient() {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
