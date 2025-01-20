<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtProvince extends Model
{
    protected $fillable = ['name', 'longitude', 'latitude'];

    public function artProvinceDetails()
    {
        return $this->hasMany(ArtProvinceDetail::class);
    }
}
