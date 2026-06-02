<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentRequest extends Model
{
    protected $fillable = ['user_id', 'tmdb_id', 'type', 'title', 'original_title', 'poster_url', 'year', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isImported(): bool { return $this->status === 'imported'; }
}
