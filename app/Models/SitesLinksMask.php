<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SitesLinksMask extends Model
{
    use HasFactory;

    protected $table = 'sites_links_masks';

    public function mask()
    {
        return $this->hasOne(LinksMask::class, 'id', 'mask_id');
    }


}
