<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'code_hash', 'expires_at', 'attempts'])]
class EmailVerificationCode extends Model
{
    protected $primaryKey = 'user_id';

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public $incrementing = false;
}
