<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

class Otp extends Model implements CipherSweetEncrypted
{
    use HasFactory;
    use UsesCipherSweet;

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addTextField('email')
            ->addBlindIndex('email', new BlindIndex('email_index'))
            ->addTextField('code')
            ->addBlindIndex('code', new BlindIndex('code_index'));
    }

    public $timestamps = false;

    protected $fillable = [
        'email',
        'code',
        'token',
        'expires_at',
        'user_id',
    ];

    public function user() {
        if ($this->user_id) {
            return $this->belongsTo(User::class, 'user_id', 'id');
        }

        return collect();
    }
}
