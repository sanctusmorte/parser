<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $table = 'sites';

    public function links()
    {
        return $this->hasMany(Link::class, 'parent_id');
    }

    public function ungropedLinks()
    {
        return $this->hasMany(Link::class, 'parent_id')->whereNull('links.mask_ids');
    }

    public function groupedLinks()
    {
        return $this->hasMany(Link::class, 'parent_id')->whereNotNull('links.mask_ids');
    }


    public function linksMasks()
    {
        return $this->hasMany(SitesLinksMask::class, 'site_id');
    }
}
