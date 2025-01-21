<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtProvince extends Model
{
    protected $fillable = ['name', 'longitude', 'latitude', 'subtitle'];

    public function artProvinceDetails()
    {
        return $this->hasMany(ArtProvinceDetail::class);
    }
}
