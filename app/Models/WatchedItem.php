<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchedItem extends Model
{
    protected $fillable = ['session_id', 'item_type', 'item_id'];
}
