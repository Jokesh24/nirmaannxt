<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessType extends Model
{
    use HasFactory;

    protected $table = 'business_types';

    protected $fillable = [
        'name',
        'status',
        'trash'
    ];

    /**
     * Get the vendors associated with this business type.
     */
    public function vendors()
    {
        return $this->hasMany(Vendor::class, 'business_type_id');
    }
}