<?php

namespace MityDigital\StatamicFormSubmissionCleaner;

use MityDigital\StatamicFormSubmissionCleaner\Console\Commands\RunCommand;
use MityDigital\StatamicFormSubmissionCleaner\Support\FormSubmissionCleaner;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        RunCommand::class,
    ];

    public function bootAddon()
    {
        $this->app->singleton('formSubmissionCleaner', function ($app) {
            return new FormSubmissionCleaner;
        });

        $this->mergeConfigFrom(
            __DIR__.'/../config/statamic-form-submission-cleaner.php',
            'statamic-form-submission-cleaner'
        );

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/statamic-form-submission-cleaner.php' => config_path(
                    'statamic-form-submission-cleaner.php'
                ),
            ], 'statamic-form-submission-cleaner');
        }
    }
}
