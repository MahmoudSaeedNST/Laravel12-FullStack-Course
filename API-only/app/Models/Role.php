<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    //
    protected $fillable = [
        'name',
    ];

    /**
     * Get the users associated with the role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    /**
     * Get the permissions associated with the role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    /**
     * Check if the role has a specific permission.
     *
     * @param string $permissionName The name of the permission to check.
     * @return bool True if the role has the permission, false otherwise.
     */

    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions->contains('name', $permissionName);
    }
}
