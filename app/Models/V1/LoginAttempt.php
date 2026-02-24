<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'ip_address',
        'attempted_at',
        'successful'
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'successful' => 'boolean'
    ];
}
