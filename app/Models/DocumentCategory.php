<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DocumentCategory extends Model
{
    protected $fillable = ['name', 'slug', 'active'];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function documents()
    {
        return $this->hasMany(PartnerDocument::class, 'category_id');
    }

    public static function booted()
    {
        static::creating(function ($category) {
            $baseSlug = Str::slug($category->name);
            $slug = $baseSlug;
            $count = 1;

            // Enquanto existir um slug igual, incrementa com _2, _3...
            while (self::where('slug', $slug)->exists()) {
                $count++;
                $slug = "{$baseSlug}_{$count}";
            }

            $category->slug = $slug;
        });

        static::updating(function ($category) {
            // mesma lógica se quiser manter atualizado
            $baseSlug = Str::slug($category->name);
            $slug = $baseSlug;
            $count = 1;

            while (self::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $count++;
                $slug = "{$baseSlug}_{$count}";
            }

            $category->slug = $slug;
        });
    }
}
