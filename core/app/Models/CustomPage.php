<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomPage extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'html_content',
        'css_content',
        'js_content',
        'head_content',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'og_title',
        'og_description',
        'canonical_url',
        'twitter_card',
        'favicon',
        'status',
    ];

    public function revisions()
    {
        return $this->hasMany(PageRevision::class)->latest('created_at');
    }

    public function isPublished(): bool
    {
        return $this->status == 1;
    }

    public function scopePublished($query)
    {
        return $query->where('status', 1);
    }

    public function getEffectiveMetaTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }
}
