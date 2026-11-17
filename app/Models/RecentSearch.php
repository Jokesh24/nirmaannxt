<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecentSearch extends Model
{
    use HasFactory;

    protected $table = 'recent_searches';

    protected $fillable = [
        'user_id',
        'keyword',
        'product_id', // Added product_id
    ];

    /**
     * Relationship: A recent search belongs to a User.
     */
    public function user()
    {
        return $this->belongsTo(AppUser::class);
    }

    /**
     * Relationship: A recent search belongs to a Product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}