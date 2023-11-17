<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pornstar extends Model
{
    use HasFactory;

    protected $table = 'pornstars';

    protected $fillable = [
        'external_id',
        'first_name',
        'last_name',
        'external_full_name',
    ];

}
