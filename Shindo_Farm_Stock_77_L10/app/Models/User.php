<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    /**
     * Get role label.
     */
    public function getRoleLabel(): string
    {
        $roles = config('roles.roles');
        return $roles[$this->role]['label'] ?? $this->role;
    }

    /**
     * Get role badge color class.
     */
    public function getRoleBadge(): string
    {
        $roles = config('roles.roles');
        return 'bg-' . ($roles[$this->role]['badge'] ?? 'secondary');
    }

    /**
     * Get role text color class.
     */
    public function getRoleTextColor(): string
    {
        $roles = config('roles.roles');
        return 'text-' . ($roles[$this->role]['color'] ?? '#6c757d');
    }

    /**
     * Get role icon.
     */
    public function getRoleIcon(): string
    {
        $roles = config('roles.roles');
        return $roles[$this->role]['icon'] ?? 'bi-person';
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(string ... $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Check if user is super_admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is admin or above.
     */
    public function isAdminOrAbove(): bool
    {
        return in_array($this->role, ['super_admin', 'admin']);
    }
}