<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'chat_count',
        'is_subscribed',
        'subscription_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ── Append computed is_subscribed to every JSON response ──
    protected $appends = ['is_subscribed'];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'subscription_expires_at' => 'datetime',
        ];
    }

    // ── is_subscribed checks both the flag AND expiry date ──
    protected function isSubscribed(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Column value (raw, before appends override)
                $flag = (bool) ($this->attributes['is_subscribed'] ?? false);

                if (!$flag) return false;

                // If no expiry set, subscription never expires
                if (is_null($this->subscription_expires_at)) return true;

                // Otherwise check if still within valid period
                return Carbon::now()->lt($this->subscription_expires_at);
            }
        );
    }
}