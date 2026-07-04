<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'phone',
    'role',
    'customer_type',
    'company_name',
    'vat_number',
    'city',
    'country',
    'status',
    'password',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function canUseParser(string $permission = 'parser.view'): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $roleNames = collect([$this->role])
            ->merge($this->relationLoaded('roles') ? $this->roles->pluck('name') : $this->roles()->pluck('name'))
            ->filter()
            ->unique()
            ->values();

        $permissions = [
            'manager' => ['parser.view', 'parser.import', 'parser.run', 'parser.approve'],
            'content_manager' => ['parser.view', 'parser.import', 'parser.run', 'parser.approve'],
        ];

        return $roleNames->contains(fn ($role) => in_array($permission, $permissions[$role] ?? [], true));
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
