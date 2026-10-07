<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pins the suite to the `mysql_testing` connection and makes requests look
     * like they come from the SPA.
     *
     * The api container exports DB_* as OS env vars, which win over
     * phpunit.xml's <env>; re-pointing the default connection here guarantees
     * tests never run against the development database.
     *
     * The Referer header is what makes Sanctum treat the request as stateful
     * (session + cookies), exactly like the SPA. Without it, login and logout
     * would have no session to work with.
     */
    protected function setUp(): void
    {
        $_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'mysql_testing';
        putenv('DB_CONNECTION=mysql_testing');

        parent::setUp();

        config(['database.default' => 'mysql_testing']);
        DB::setDefaultConnection('mysql_testing');

        $this->withHeader('Referer', 'http://localhost');
    }

    /**
     * Turns tenancy on for this test with "localhost" as the central domain:
     * tenants answer at http://<slug>.localhost.
     */
    protected function enableTenancy(): void
    {
        config(['tenancy.enabled' => true, 'tenancy.central_domains' => ['localhost']]);
    }

    /** Absolute URL of an API path on a tenant's host (null: central). */
    protected function onTenant(?string $slug, string $path): string
    {
        return 'http://'.($slug ? $slug.'.' : '').'localhost'.$path;
    }

    /**
     * The app instance survives across requests within a test, and Sanctum's
     * guard caches the first user it resolved: switching actors mid-test would
     * keep authenticating as the previous one and hide authorization bugs.
     */
    public function actingAs(UserContract $user, $guard = null)
    {
        $this->app['auth']->forgetGuards();

        return parent::actingAs($user, $guard);
    }
}
