<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HolidayImportLog extends Model
{
    protected $fillable = [
        'provider', 'year', 'state', 'scope', 'scope_id', 'created_count', 'updated_count', 'created_by',
    ];
}
