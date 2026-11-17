<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = ['banner_name', 'image', 'banner_type_id', 'status'];

    public function bannerType()
    {
        return $this->belongsTo(BannerType::class, 'banner_type_id');
    }
}