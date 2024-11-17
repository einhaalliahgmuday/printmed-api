<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

class Registration extends Model implements CipherSweetEncrypted
{
    use HasFactory;
    use UsesCipherSweet;

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addField('full_name')
            ->addBlindIndex('full_name', new BlindIndex('full_name_index'))
            ->addField('first_name')
            ->addBlindIndex('first_name', new BlindIndex('first_name_index'))
            ->addOptionalTextField('middle_name')
            ->addField('last_name')
            ->addBlindIndex('last_name', new BlindIndex('last_name_index'))
            ->addOptionalTextField('suffix')
            ->addOptionalTextField('birthdate')
            ->addBlindIndex('birthdate', new BlindIndex('birthdate_index'))
            ->addOptionalTextField('birthplace')
            ->addOptionalTextField('sex')
            ->addBlindIndex('sex', new BlindIndex('sex_index'))
            ->addOptionalTextField('address')
            ->addOptionalTextField('civil_status')
            ->addOptionalTextField('religion')
            ->addOptionalTextField('phone_number')
            ->addOptionalTextField('email')
            ->addOptionalTextField('photo');
    }

    protected $fillable = [
        'full_name',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birthdate',
        'birthplace',
        'sex',
        'address',
        'civil_status',
        'religion',
        'phone_number',
        'email',
        'photo'
    ];
}
