<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable('name', 'slug', 'description', 'image', 'parent_id', 'icon', 'color', 'status', 'sort_order')]
class Category extends Model
{
    //
}
