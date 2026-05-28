<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $table = 'user_notifications';
    protected $fillable = ['user_id', 'type', 'title', 'message', 'url', 'read'];
    protected $casts = ['read' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }

    public static function notifyAll(string $type, string $title, string $message, ?string $url = null): void
    {
        $users = User::pluck('id');
        $now   = now();
        $rows  = $users->map(fn($id) => [
            'user_id'    => $id,
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'url'        => $url,
            'read'       => false,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();
        static::insert($rows);
    }
}
