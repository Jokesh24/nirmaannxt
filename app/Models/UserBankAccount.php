<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBankAccount extends Model
{
    use HasFactory;

    protected $table = 'user_bank_accounts';

    protected $fillable = [
        'user_id',
        'bank_id',
        'account_holder_name',
        'account_number',
        'ifsc_code',
        'upi_id',
        'is_selected'
    ];

    /**
     * Relationship to the AppUser
     */
    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    /**
     * Relationship to the Bank
     */
    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }
}