<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }


    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }
    /**
     * Check if the user has a specific role.
     *
     * @param  string  $roleName
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName);
    }

    /**
     * Determine if the user has a specific permission.
     *
     * This method checks if any of the user's roles grant the specified permission.
     *
     * @param string $permissionName The name of the permission to check.
     * @return bool True if the user has the permission, false otherwise.
     */
    
    public function hasPermission(string $permissionName): bool
    {
        return $this->roles->contains(fn($role) => $role->hasPermission($permissionName));
    }
}
