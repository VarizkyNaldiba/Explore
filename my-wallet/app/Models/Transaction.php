<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
        'description',
        'email',
        'bank_type',
        'source',
        'transaction_date',
    ];

    protected $casts = [
        'amount' => 'double',
        'transaction_date' => 'datetime',
    ];
}
