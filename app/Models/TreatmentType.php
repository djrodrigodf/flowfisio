<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreatmentType extends Model
{
    protected $fillable = ['name', 'slug', 'active'];
}
