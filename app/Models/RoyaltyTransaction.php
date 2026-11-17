<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoyaltyTransaction extends Model
{
    use HasFactory;

    protected $table = 'royality_transactions';

    protected $fillable = [
        'user_id',
        'points',
        'transaction_id',
        'type',
        'reason',
        'status',
        'trash',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}