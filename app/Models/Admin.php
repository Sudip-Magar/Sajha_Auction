<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'role', 'position', 'date_of_joining', 'gender', 'password', 'avatar', 'address', 'status'])]
#[Hidden(['password', 'remember_token'])]

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Get the channels the event should broadcast on.
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'App.Models.Admin.'.$this->id;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function gender(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value === null || $value === '' ? null : strtolower($value),
        );
    }
}
