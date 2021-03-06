<?php

namespace Konekt\Acl\Traits;

use Illuminate\Support\Collection;
use Konekt\Acl\Models\PermissionProxy;
use Konekt\Acl\PermissionRegistrar;
use Konekt\Acl\Contracts\Permission;
use Konekt\Acl\Exceptions\GuardDoesNotMatch;

trait HasPermissions
{
    /**
     * Grant the given permission(s) to a role.
     *
     * @param string|array|\Konekt\Acl\Contracts\Permission|\Illuminate\Support\Collection $permissions
     *
     * @return $this
     */
    public function givePermissionTo(...$permissions)
    {
        $permissions = collect($permissions)
            ->flatten()
            ->map(function ($permission) {
                return $this->getStoredPermission($permission);
            })
            ->each(function ($permission) {
                $this->ensureModelSharesGuard($permission);
            })
            ->all();

        $this->permissions()->saveMany($permissions);

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Remove all current permissions and set the given ones.
     *
     * @param string|array|\Konekt\Acl\Contracts\Permission|\Illuminate\Support\Collection $permissions
     *
     * @return $this
     */
    public function syncPermissions(...$permissions)
    {
        $this->permissions()->detach();

        return $this->givePermissionTo($permissions);
    }

    /**
     * Revoke the given permission.
     *
     * @param \Konekt\Acl\Contracts\Permission|string $permission
     *
     * @return $this
     */
    public function revokePermissionTo($permission)
    {
        $this->permissions()->detach($this->getStoredPermission($permission));

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * @param string|array|\Konekt\Acl\Contracts\Permission|\Illuminate\Support\Collection $permissions
     *
     * @return \Konekt\Acl\Contracts\Permission
     */
    protected function getStoredPermission($permissions): Permission
    {
        if (is_string($permissions)) {
            return PermissionProxy::findByName($permissions, $this->getDefaultGuardName());
        }

        if (is_array($permissions)) {
            return PermissionProxy::whereIn('name', $permissions)
                ->whereId('guard_name', $this->getGuardNames())
                ->get();
        }

        return $permissions;
    }

    /**
     * @param \Konekt\Acl\Contracts\Permission|\Konekt\Acl\Contracts\Role $roleOrPermission
     *
     * @throws \Konekt\Acl\Exceptions\GuardDoesNotMatch
     */
    protected function ensureModelSharesGuard($roleOrPermission)
    {
        if (! $this->getGuardNames()->contains($roleOrPermission->guard_name)) {
            throw GuardDoesNotMatch::create($roleOrPermission->guard_name, $this->getGuardNames());
        }
    }

    protected function getGuardNames(): Collection
    {
        if ($this->guard_name) {
            return collect($this->guard_name);
        }

        return collect(config('auth.guards'))
            ->map(function ($guard) {
                return config("auth.providers.{$guard['provider']}.model");
            })
            ->filter(function ($model) {
                return get_class($this) === $model;
            })
            ->keys();
    }

    protected function getDefaultGuardName(): string
    {
        $default = config('auth.defaults.guard');

        return $this->getGuardNames()->first() ?: $default;
    }

    /**
     * Forget the cached permissions.
     */
    public function forgetCachedPermissions()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
