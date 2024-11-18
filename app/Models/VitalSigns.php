<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

class VitalSigns extends Model implements CipherSweetEncrypted
{
    use HasFactory;
    use UsesCipherSweet;

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addOptionalTextField('height')
            ->addOptionalTextField('weight')
            ->addOptionalTextField('blood_pressure')
            ->addOptionalTextField('temperature');
    }

    protected $fillable = [
        'height',
        'height_unit',
        'weight',
        'weight_unit',
        'temperature',
        'temperature_unit',
        'blood_pressure',
        'patient_id'
    ];
}
