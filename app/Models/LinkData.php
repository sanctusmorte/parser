<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkData extends Model
{
    use HasFactory;

    protected $table = 'link_data';

    protected $primaryKey = 'id';

    protected $fillable =[
        'parent_link_id',
        'parent_site_id',
        'type',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'h_tags',
        'img_alts',
        'href_titles',
        'is_video_content',
        'is_redirect',
        'content_type',
        'thumbs_types',
    ];
}
