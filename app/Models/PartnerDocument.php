<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PartnerDocument extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['partner_id', 'category', 'description'];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }
}
