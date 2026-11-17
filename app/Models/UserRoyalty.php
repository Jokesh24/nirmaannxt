<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRoyalty extends Model
{
    use HasFactory;

    protected $table = 'user_royalities';

    protected $fillable = [
        'user_id',
        'royality_points',
        'status',
        'trash',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}