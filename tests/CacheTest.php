<?php

namespace Konekt\Acl\Test;

use Illuminate\Support\Facades\DB;
use Konekt\Acl\Models\PermissionProxy;
use Konekt\Acl\Models\RoleProxy;
use Konekt\Acl\PermissionRegistrar;

class CacheTest extends TestCase
{
    protected $registrar;

    public function setUp()
    {
        parent::setUp();

        $this->registrar = app(PermissionRegistrar::class);

        $this->registrar->forgetCachedPermissions();

        DB::connection()->enableQueryLog();

        $this->assertCount(0, DB::getQueryLog());

        $this->registrar->registerPermissions();

        $this->assertCount(2, DB::getQueryLog());

        DB::flushQueryLog();
    }

    /** @test */
    public function it_can_cache_the_permissions()
    {
        $this->registrar->registerPermissions();

        $this->assertCount(0, DB::getQueryLog());
    }

    /** @test */
    public function permission_creation_and_updating_should_flush_the_cache()
    {
        $permission = PermissionProxy::create(['name' => 'new']);
        $this->assertCount(1, DB::getQueryLog());

        $this->registrar->registerPermissions();
        $this->assertCount(3, DB::getQueryLog());

        $permission->name = 'other name';
        $permission->save();
        $this->assertCount(4, DB::getQueryLog());

        $this->registrar->registerPermissions();
        $this->assertCount(6, DB::getQueryLog());
    }

    /** @test */
    public function role_creation_and_updating_should_flush_the_cache()
    {
        $role = RoleProxy::create(['name' => 'new']);
        $this->assertCount(2, DB::getQueryLog());

        $this->registrar->registerPermissions();
        $this->assertCount(4, DB::getQueryLog());

        $role->name = 'other name';
        $role->save();
        $this->assertCount(5, DB::getQueryLog());

        $this->registrar->registerPermissions();
        $this->assertCount(7, DB::getQueryLog());
    }

    /** @test */
    public function user_creation_should_not_flush_the_cache()
    {
        User::create(['email' => 'new']);
        $this->assertCount(1, DB::getQueryLog());

        $this->registrar->registerPermissions();
        $this->assertCount(1, DB::getQueryLog());
    }

    /** @test */
    public function adding_a_permission_to_a_role_should_flush_the_cache()
    {
        $this->testUserRole->givePermissionTo($this->testUserPermission);
        $this->assertCount(1, DB::getQueryLog());

        $this->registrar->registerPermissions();
        $this->assertCount(3, DB::getQueryLog());
    }

    /** @test */
    public function has_permission_to_should_use_the_cache()
    {
        $this->testUserRole->givePermissionTo(['edit-articles', 'edit-news']);
        $this->testUser->assignRole('testRole');
        $this->assertCount(4, DB::getQueryLog());

        $this->assertTrue($this->testUser->hasPermissionTo('edit-articles'));
        $this->assertCount(8, DB::getQueryLog());

        $this->assertTrue($this->testUser->hasPermissionTo('edit-news'));
        $this->assertCount(8, DB::getQueryLog());

        $this->assertTrue($this->testUser->hasPermissionTo('edit-articles'));
        $this->assertCount(8, DB::getQueryLog());
    }
}
