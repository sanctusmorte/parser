<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinksMask extends Model
{
    use HasFactory;
    use \Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

    protected $table = 'links_masks';

    protected $casts = [
        'mask_ids' => 'json'
    ];

    public function links()
    {
        return $this->belongsToJson(Link::class, 'mask_ids', 'id');
    }
}
