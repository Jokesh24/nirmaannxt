<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory;

    protected $table = 'user_notifications';

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'image',
        'type',
        'device_token',
        'is_sent',
        'is_admin_sent',
        'is_seen',
        'trash'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}