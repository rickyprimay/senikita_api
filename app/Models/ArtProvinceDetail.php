<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArtProvinceDetail extends Model
{
    protected $guarded = [];

    public function artProvince()
    {
        return $this->belongsTo(ArtProvince::class);
    }
}
