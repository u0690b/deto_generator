<?php

namespace Deto\Generator;

use Illuminate\Support\ServiceProvider;
use Deto\Generator\Commands\API\APIControllerGeneratorCommand;
use Deto\Generator\Commands\API\APIGeneratorCommand;
use Deto\Generator\Commands\API\APIRequestsGeneratorCommand;
use Deto\Generator\Commands\API\TestsGeneratorCommand;
use Deto\Generator\Commands\APIScaffoldGeneratorCommand;
use Deto\Generator\Commands\Common\MigrationGeneratorCommand;
use Deto\Generator\Commands\Common\ModelGeneratorCommand;
use Deto\Generator\Commands\Common\RepositoryGeneratorCommand;
use Deto\Generator\Commands\Publish\GeneratorPublishCommand;
use Deto\Generator\Commands\Publish\LayoutPublishCommand;
use Deto\Generator\Commands\Publish\PublishTemplateCommand;
use Deto\Generator\Commands\Publish\PublishUserCommand;
use Deto\Generator\Commands\RollbackGeneratorCommand;
use Deto\Generator\Commands\Scaffold\ControllerGeneratorCommand;
use Deto\Generator\Commands\Scaffold\RequestsGeneratorCommand;
use Deto\Generator\Commands\Scaffold\ScaffoldGeneratorCommand;
use Deto\Generator\Commands\Scaffold\ViewsGeneratorCommand;

class DetoGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__.'/../config/laravel_generator.php';

        $this->publishes([
            $configPath => config_path('deto/laravel_generator.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('deto.publish', function ($app) {
            return new GeneratorPublishCommand();
        });

        $this->app->singleton('deto.api', function ($app) {
            return new APIGeneratorCommand();
        });

        $this->app->singleton('deto.scaffold', function ($app) {
            return new ScaffoldGeneratorCommand();
        });

        $this->app->singleton('deto.publish.layout', function ($app) {
            return new LayoutPublishCommand();
        });

        $this->app->singleton('deto.publish.templates', function ($app) {
            return new PublishTemplateCommand();
        });

        $this->app->singleton('deto.api_scaffold', function ($app) {
            return new APIScaffoldGeneratorCommand();
        });

        $this->app->singleton('deto.migration', function ($app) {
            return new MigrationGeneratorCommand();
        });

        $this->app->singleton('deto.model', function ($app) {
            return new ModelGeneratorCommand();
        });

        $this->app->singleton('deto.repository', function ($app) {
            return new RepositoryGeneratorCommand();
        });

        $this->app->singleton('deto.api.controller', function ($app) {
            return new APIControllerGeneratorCommand();
        });

        $this->app->singleton('deto.api.requests', function ($app) {
            return new APIRequestsGeneratorCommand();
        });

        $this->app->singleton('deto.api.tests', function ($app) {
            return new TestsGeneratorCommand();
        });

        $this->app->singleton('deto.scaffold.controller', function ($app) {
            return new ControllerGeneratorCommand();
        });

        $this->app->singleton('deto.scaffold.requests', function ($app) {
            return new RequestsGeneratorCommand();
        });

        $this->app->singleton('deto.scaffold.views', function ($app) {
            return new ViewsGeneratorCommand();
        });

        $this->app->singleton('deto.rollback', function ($app) {
            return new RollbackGeneratorCommand();
        });

        $this->app->singleton('deto.publish.user', function ($app) {
            return new PublishUserCommand();
        });

        $this->commands([
            'deto.publish',
            'deto.api',
            'deto.scaffold',
            'deto.api_scaffold',
            'deto.publish.layout',
            'deto.publish.templates',
            'deto.migration',
            'deto.model',
            'deto.repository',
            'deto.api.controller',
            'deto.api.requests',
            'deto.api.tests',
            'deto.scaffold.controller',
            'deto.scaffold.requests',
            'deto.scaffold.views',
            'deto.rollback',
            'deto.publish.user',
        ]);
    }
}
