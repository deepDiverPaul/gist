<?php

namespace Gist;

use Silex\Application as SilexApplication;

/**
 * @deprecated The static version should be avoided, use DI instead
 */
class Application extends SilexApplication
{
    /**
     * Creates an instance of Application.
     *
     * @static
     *
     * @return Application
     */
    public static function getInstance()
    {
        static $app;

        if (null === $app) {
            $app = new static();
        }

        return $app;
    }
}
