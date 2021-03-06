<?php

namespace Konekt\Acl\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Konekt\Acl\Exceptions\RoleDoesNotExist;

interface Role
{
    /**
     * A role may be given various permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany;

    /**
     * Find a role by its name and guard name.
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @return \Konekt\Acl\Contracts\Role
     *
     * @throws RoleDoesNotExist
     */
    public static function findByName(string $name, $guardName): Role;

    /**
     * Determine if the user may perform the given permission.
     *
     * @param string|Permission $permission
     *
     * @return bool
     */
    public function hasPermissionTo($permission): bool;
}
