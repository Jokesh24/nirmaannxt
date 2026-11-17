<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannerType extends Model
{
protected $fillable = ['type_name', 'status'];

public function banners()
{
return $this->hasMany(Banner::class, 'banner_type_id');
}
}