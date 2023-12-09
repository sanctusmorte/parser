<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

class Link extends Model
{
    use HasFactory;
    use HasJsonRelationships;

    protected $table = 'links';

    protected $fillable =[
        'link_url',
        'parent_id',
        'status',
        'level',
        'level',
    ];

    public function getMaskNames()
    {
        return LinksMask::whereIn('id', json_decode($this->mask_ids, 1))->get()->pluck('name')->toArray();
    }
}
