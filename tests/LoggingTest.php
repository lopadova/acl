<?php

namespace Konekt\Acl\Test;

use Monolog\Logger;

class LoggingTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    /** @test */
    public function it_logs_when_config_is_set_to_true()
    {
        $this->app['config']->set('konekt.acl.log_registration_exception', true);

        (new \CreateAclTables())->down();

        $this->reloadPermissions();

        $this->assertLogged('Could not register permissions', Logger::ALERT);
    }

    /** @test */
    public function it_doesnt_log_when_config_is_set_to_false()
    {
        $this->app['config']->set('konekt.acl.log_registration_exception', false);

        (new \CreateAclTables())->down();

        $this->reloadPermissions();

        $this->assertNotLogged('Could not register permissions', Logger::ALERT);
    }
}
