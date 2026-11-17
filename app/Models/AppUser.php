<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

// UPDATE THIS LINE BELOW

class AppUser extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $table = 'app_users';

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'otp',
        'otp_status',
        'device_token',
        'latitude',
        'longitude',
        'user_type',
        'profile_photo',
        'gender',
        'status',
        'customer_code'
    ];

    protected $hidden = ['otp', 'device_token'];

    // Recommended: Add this to help the Mobile App get the full URL easily
    public function getProfilePhotoAttribute($value)
    {
        return $value ? asset($value) : null;
    }

    /**
     * JWT Required Methods
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function memberships()
    {
        return $this->hasMany(UserMembership::class, 'user_id');
    }


    // Inside AppUser.php
    public function eventBookings()
    {
        return $this->hasMany(EventBooking::class, 'user_id');
    }

    public function emmadiBookings()
    {
        return $this->hasMany(EmmadiBooking::class, 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'user_id');
    }

    public function bulkCarts()
    {
        return $this->hasMany(BulkCart::class, 'user_id');
    }

    public function bulkQuoteRequests()
    {
        return $this->hasMany(BulkQuoteRequest::class, 'user_id');
    }
}