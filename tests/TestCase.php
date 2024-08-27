<?php

namespace MityDigital\StatamicFormSubmissionCleaner\Tests;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\File;
use MityDigital\StatamicFormSubmissionCleaner\ServiceProvider;
use Statamic\Facades\Blueprint;
use Statamic\Statamic;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected bool $shouldFakeVersion = true;

    protected string $addonServiceProvider = ServiceProvider::class;

    /*protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        if ($this->shouldFakeVersion) {
            Version::shouldReceive('get')
                ->andReturn(Composer::create(__DIR__.'/../')->installedVersion(Statamic::PACKAGE));
        }
    }*/

    /*protected function getPackageProviders($app)
    {
        return [
            StatamicServiceProvider::class,
            ServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Statamic' => Statamic::class,
        ];
    }*/

    /*protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $app->make(Manifest::class)->manifest = [
            'mitydigital/statamic-form-submission-cleaner' => [
                'id' => 'mitydigital/statamic-form-submission-cleaner',
                'namespace' => 'MityDigital\\StatamicFormSubmissionCleaner',
            ],
        ];
    }*/

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $configs = [
            'assets',
            'forms',
            'stache',
        ];

        foreach ($configs as $config) {
            $app['config']->set(
                "statamic.$config",
                require (__DIR__."/../vendor/statamic/cms/config/{$config}.php")
            );
        }

        $app['config']->set('app.key', 'base64:'.base64_encode(
            Encrypter::generateKey($app['config']['app.cipher'])
        )
        );

        $app['config']->set('filesystems.disks.assets', [
            'driver' => 'local',
            'root' => $this->getTempDirectory('/content/assets'),
            'url' => '/assets',
            'visibility' => 'public',
        ]);

        // assets
        $app['config']->set('statamic.stache.stores.asset-containers.directory',
            $this->getTempDirectory('/content/assets'));

        // forms
        $app['config']->set('statamic.forms.forms',
            $this->getTempDirectory('/forms'));

        // forms submissions
        $app['config']->set('statamic.stache.stores.form-submissions.directory',
            $this->getTempDirectory('/content/submissions'));

        Statamic::booted(function () {
            Blueprint::setDirectory(__DIR__.'/__fixtures__/blueprints');
        });
    }

    public function getTempDirectory($suffix = ''): string
    {
        return __DIR__.'/TestSupport/'.($suffix == '' ? '' : '/'.$suffix);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->getTempDirectory());

        parent::tearDown();
    }
}
