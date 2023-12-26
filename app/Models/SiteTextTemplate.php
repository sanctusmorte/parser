<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteTextTemplate extends Model
{
    use HasFactory;

    protected $table = 'site_text_templates';

    public function sites()
    {
        return $this->hasMany(Site::class, 'text_template_id');
    }
}
