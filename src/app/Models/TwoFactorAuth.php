<?php

namespace MHMartinez\TwoFactorAuth\app\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AlertAdmin
 *
 * @property int $id
 * @property int $user_id
 * @property string $secret
 * @property Carbon $updated_at
 */
class TwoFactorAuth extends Model
{
    protected $table = 'two_factor_auth';
    protected $fillable = ['user_id', 'secret', 'setup_confirmed_at'];
    protected $dates = ['setup_confirmed_at'];

    public function setSecretAttribute(string $value): void
    {
        $this->attributes['secret'] = encrypt($value);
    }
}