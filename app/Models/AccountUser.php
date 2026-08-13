<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AccountUser extends Pivot
{
    protected $table = 'account_user';

    protected $fillable = [
        'account_id',
        'user_id',
        'role',
    ];
}
