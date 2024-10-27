<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

class ResetToken extends Model implements CipherSweetEncrypted
{
    use HasFactory;
    use UsesCipherSweet;

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addTextField('email')
            ->addBlindIndex('email', new BlindIndex('email_index'));
    }

    public $timestamps = false;

    protected $fillable = [
        'email',
        'token',
        'expires_at'
    ];
}
