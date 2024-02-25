<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;
    protected $fillable = [
        'type', // Only allow 'direct' or 'group' for type
        'name', // Make name optional for direct messages
        'icon',
        'icon_thumbnail',
    ];

    // Relationships
    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_users');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
