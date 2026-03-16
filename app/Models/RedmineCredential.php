<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedmineCredential extends Model
{
    protected $fillable = [
        'username',
        'password_encrypted',
        'api_key_encrypted',
        'use_api',
    ];
}

