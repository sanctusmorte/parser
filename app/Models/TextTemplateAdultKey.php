<?php

namespace App\Models;

use App\Services\TextTemplate\Enum\DictionaryTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TextTemplateAdultKey extends Model
{
    use HasFactory;

    protected $table = DictionaryTypeEnum::ADULTKEY;
}
