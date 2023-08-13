<?php

namespace OpenDeveloper\Developer\Reporter;

use Illuminate\Routing\Router;
use OpenDeveloper\Developer\Developer;

trait BootExtension
{
    public static function boot()
    {
        static::registerRoutes();

        static::importAssets();

        Developer::extend('reporter', __CLASS__);
    }

    /**
     * Register routes for open-developer.
     *
     * @return void
     */
    protected static function registerRoutes()
    {
        parent::routes(function ($router) {
            /* @var Router $router */
            $router->resource('exceptions', 'OpenDeveloper\Developer\Reporter\ExceptionController');
        });
    }

    /**
     * {@inheritdoc}
     */
    public static function import()
    {
        parent::createMenu('Exception Reporter', 'exceptions', 'icon-bug');

        parent::createPermission('Exceptions reporter', 'ext.reporter', 'exceptions*');
    }

    public static function importAssets()
    {
        Developer::js('/vendor/open-developer-reporter/prism/prism.js');
        Developer::css('/vendor/open-developer-reporter/prism/prism.css');
    }
}
