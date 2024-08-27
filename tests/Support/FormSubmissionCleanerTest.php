<?php

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use MityDigital\StatamicFormSubmissionCleaner\Support\FormSubmissionCleaner;
use Mockery\MockInterface;
use Statamic\Assets\AssetContainer;
use Statamic\Facades\Asset;
use Statamic\Facades\Form;
use Statamic\Forms\Submission;
use Statamic\Query\Scopes\Scope;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    $this->support = app(FormSubmissionCleaner::class);
});

//
// SCOPES
//
it('returns the correct structure when nothing provided', function () {
    $scopes = callProtectedMethod($this->support, 'scopes', ['config' => []]);
    expect($scopes)->toBeInstanceOf(Collection::class)
        ->toHaveCount(0);
});

it('returns the correct structure when null provided', function () {
    $scopes = callProtectedMethod($this->support, 'scopes', [
        'config' => [
            'query_scopes' => null,
        ],
    ]);
    expect($scopes)->toBeInstanceOf(Collection::class)
        ->toHaveCount(0);
});

it('returns the correct structure when single scope provided', function () {
    $scopes = callProtectedMethod($this->support, 'scopes', [
        'config' => [
            'query_scopes' => TestScope::class,
        ],
    ]);

    expect($scopes)->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($scopes->get(0))->toBeInstanceOf(TestScope::class);
});

it('returns the correct structure when array provided', function () {
    $scopes = callProtectedMethod($this->support, 'scopes', [
        'config' => [
            'query_scopes' => [TestScope::class],
        ],
    ]);

    expect($scopes)->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($scopes->get(0))->toBeInstanceOf(TestScope::class);

    $scopes = callProtectedMethod($this->support, 'scopes', [
        'config' => [
            'query_scopes' => [AnotherTestScope::class, TestScope::class],
        ],
    ]);

    expect($scopes)->toBeInstanceOf(Collection::class)
        ->toHaveCount(2)
        ->and($scopes->get(0))->toBeInstanceOf(AnotherTestScope::class)
        ->and($scopes->get(1))->toBeInstanceOf(TestScope::class);
});

//
// CONFIG
//
it('has the expected default config options', function () {
    expect(config('statamic-form-submission-cleaner.defaults.days'))->toBe(30)
        ->and(config('statamic-form-submission-cleaner.defaults.delete_assets'))->toBeTrue()
        ->and(config('statamic-form-submission-cleaner.defaults.delete_submissions'))->toBeTrue()
        ->and(config('statamic-form-submission-cleaner.defaults.query_scopes'))->toBeNull()
        ->and(config('statamic-form-submission-cleaner.forms'))->toBe('all');
});

it('has the correct default config and returns all forms', function () {
    $formA = Form::make('form_a')
        ->title('Form A');
    $formA->save();

    expect(config('statamic-form-submission-cleaner.forms'))->toBe('all');

    $config = $this->support->getFormConfig();

    expect($config)->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($config->has('form_a'))
        ->toBeTrue()
        ->and($config->get('form_a'))
        ->toBeArray()
        ->toMatchArray([
            'days' => 30,
            'delete_assets' => true,
            'delete_submissions' => true,
            'assets_fields' => collect([]),
            'query_scopes' => null,
        ]);

    $formB = Form::make('form_b')
        ->title('Form B');
    $formB->save();

    $config = $this->support->getFormConfig();

    expect($config)->toBeInstanceOf(Collection::class)
        ->toHaveCount(2)
        ->and($config->has(['form_a', 'form_b']))
        ->toBeTrue()
        ->and($config->get('form_a'))
        ->toBeArray()
        ->toMatchArray([
            'days' => 30,
            'delete_assets' => true,
            'delete_submissions' => true,
            'assets_fields' => collect([]),
            'query_scopes' => null,
        ])
        ->and($config->get('form_b'))
        ->toBeArray()
        ->toMatchArray([
            'days' => 30,
            'delete_assets' => true,
            'delete_submissions' => true,
            'assets_fields' => collect([]),
            'query_scopes' => null,
        ]);
});

it('correctly has default config for a single form', function () {
    // setup
    $formA = Form::make('form_a')
        ->title('Form A');
    $formA->save();
    $formB = Form::make('form_b')
        ->title('Form B');
    $formB->save();

    config()->set('statamic-form-submission-cleaner.forms', 'form_a');

    // base
    expect(config('statamic-form-submission-cleaner.forms'))->toBe('form_a');

    $config = $this->support->getFormConfig();

    expect($config)->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($config->has('form_a'))
        ->toBeTrue()
        ->and($config->get('form_a'))
        ->toBeArray()
        ->toMatchArray([
            'days' => 30,
            'delete_assets' => true,
            'delete_submissions' => true,
            'assets_fields' => collect([]),
            'query_scopes' => null,
        ]);

    config()->set('statamic-form-submission-cleaner.forms', 'form_b');

    // base
    expect(config('statamic-form-submission-cleaner.forms'))->toBe('form_b');

    $config = $this->support->getFormConfig();

    expect($config)->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($config->has('form_b'))
        ->toBeTrue()
        ->and($config->get('form_b'))
        ->toBeArray()
        ->toMatchArray([
            'days' => 30,
            'delete_assets' => true,
            'delete_submissions' => true,
            'assets_fields' => collect([]),
            'query_scopes' => null,
        ]);
});

it('correctly has default config for an array of forms', function () {
    // setup
    $formA = Form::make('form_a')
        ->title('Form A');
    $formA->save();
    $formB = Form::make('form_b')
        ->title('Form B');
    $formB->save();

    config()->set('statamic-form-submission-cleaner.forms', ['form_a']);

    // base
    expect(config('statamic-form-submission-cleaner.forms'))
        ->toBeArray()
        ->toHaveCount(1)
        ->toMatchArray(['form_a']);

    $config = $this->support->getFormConfig();

    expect($config)->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($config->has('form_a'))
        ->toBeTrue()
        ->and($config->get('form_a'))
        ->toBeArray()
        ->toMatchArray([
            'days' => 30,
            'delete_assets' => true,
            'delete_submissions' => true,
            'assets_fields' => collect([]),
            'query_scopes' => null,
        ]);

    config()->set('statamic-form-submission-cleaner.forms', ['form_b']);

    // base
    expect(config('statamic-form-submission-cleaner.forms'))
        ->toBeArray()
        ->toHaveCount(1)
        ->toMatchArray(['form_b']);

    $config = $this->support->getFormConfig();

    expect($config)->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($config->has('form_b'))
        ->toBeTrue()
        ->and($config->get('form_b'))
        ->toBeArray()
        ->toMatchArray([
            'days' => 30,
            'delete_assets' => true,
            'delete_submissions' => true,
            'assets_fields' => collect([]),
            'query_scopes' => null,
        ]);

    config()->set('statamic-form-submission-cleaner.forms', ['form_a', 'form_b']);

    // base
    expect(config('statamic-form-submission-cleaner.forms'))
        ->toBeArray()
        ->toHaveCount(2)
        ->toMatchArray(['form_a', 'form_b']);

    $config = $this->support->getFormConfig();

    expect($config)->toBeInstanceOf(Collection::class)
        ->toHaveCount(2)
        ->and($config->has(['form_a', 'form_b']))
        ->toBeTrue()
        ->and($config->get('form_a'))
        ->toBeArray()
        ->toMatchArray([
            'days' => 30,
            'delete_assets' => true,
            'delete_submissions' => true,
            'assets_fields' => collect([]),
            'query_scopes' => null,
        ])
        ->and($config->get('form_b'))
        ->toBeArray()
        ->toMatchArray([
            'days' => 30,
            'delete_assets' => true,
            'delete_submissions' => true,
            'assets_fields' => collect([]),
            'query_scopes' => null,
        ]);
});

it('correctly overrides defaults for a specific form', function ($property, $default, $value) {
    // setup
    $formA = Form::make('form_a')
        ->title('Form A');
    $formA->save();
    $formB = Form::make('form_b')
        ->title('Form B');
    $formB->save();

    config()->set('statamic-form-submission-cleaner.forms', [
        'form_b' => [
            $property => $value,
        ],
    ]);

    // base
    expect(config('statamic-form-submission-cleaner.defaults.'.$property))->toBe($default)
        ->and(config('statamic-form-submission-cleaner.forms'))
        ->toBeArray()
        ->toHaveCount(1)
        ->toHaveKeys(['form_b'])
        ->toMatchArray([
            'form_b' => [
                $property => $value,
            ],
        ]);

    $config = $this->support->getFormConfig();

    expect($config)->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($config->has('form_b'))
        ->toBeTrue()
        ->and($config->get('form_b'))
        ->toBeArray()
        ->toMatchArray([
            $property => $value,
        ]);
})->with([
    ['days', 30, 6],
    ['delete_assets', true, false],
    ['delete_submissions', true, false],
    ['query_scopes', null, TestScope::class],
    ['query_scopes', null, [TestScope::class, AnotherTestScope::class]],
]);

it('correctly allows string and array form configurations', function () {
    // setup
    $formA = Form::make('form_a')
        ->title('Form A');
    $formA->save();
    $formB = Form::make('form_b')
        ->title('Form B');
    $formB->save();

    config()->set('statamic-form-submission-cleaner.forms', [
        'form_a',
        'form_b' => [
            'days' => 5,
            'delete_submissions' => false,
        ],
    ]);

    $config = $this->support->getFormConfig();

    expect($config)->toBeInstanceOf(Collection::class)
        ->toHaveCount(2)
        ->and($config->has(['form_a', 'form_b']))
        ->toBeTrue()
        // form a defaults
        ->and($config->get('form_a'))
        ->toBeArray()
        ->toMatchArray([
            'days' => 30,
            'delete_assets' => true,
            'delete_submissions' => true,
            'assets_fields' => collect([]),
            'query_scopes' => null,
        ])
        // form b overrides
        ->and($config->get('form_b'))
        ->toBeArray()
        ->toMatchArray([
            'days' => 5,
            'delete_assets' => true,
            'delete_submissions' => false,
            'assets_fields' => collect([]),
            'query_scopes' => null,
        ]);
});

it('correctly finds the asset fields within a form', function () {
    // setup
    $form = Form::make('form_with_assets')
        ->title('Form With Assets');
    $form->save();

    $config = $this->support->getFormConfig();

    expect($config)->toBeInstanceOf(Collection::class)
        ->toHaveCount(1)
        ->and($config->has('form_with_assets'))
        ->toBeTrue()
        ->and($config->get('form_with_assets'))
        ->toBeArray()
        ->toMatchArray([
            'days' => 30,
            'delete_assets' => true,
            'delete_submissions' => true,
            'query_scopes' => null,
        ])
        ->and($config->get('form_with_assets')['assets_fields'])
        ->not()->tohaveKeys(['name'])
        ->toHaveCount(2)
        ->toHaveKeys(['assets', 'another_assets']);
});

//
// CLEANUP
//
it('correctly determines the submissions to clean up at 29 days', function () {
    $form = Form::make('form_a')
        ->title('Form A');
    $form->save();
    $form = Form::find('form_a');

    File::deleteDirectory(__DIR__.'/../__fixtures__/content/submissions/form_a');

    $submission = $form->makeSubmission()
        ->data(['name' => 'Name']);
    $submission->save();

    // 29 days
    testTime()->freeze(Carbon::now()->addDays(29));
    $mock = $this->partialMock(FormSubmissionCleaner::class, function (MockInterface $mock) {
        $mock
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('cleanupSubmission')->times(0);
    });
    $mock->cleanup();
});

it('correctly determines the submissions to clean up at 30 days', function () {
    $form = Form::make('form_a')
        ->title('Form A');
    $form->save();

    $formB = Form::make('form_b')
        ->title('Form B');
    $formB->save();

    $form = Form::find('form_a');

    File::deleteDirectory(__DIR__.'/../__fixtures__/content/submissions/form_a');

    $submission = $form->makeSubmission()
        ->data(['name' => 'Name']);
    $submission->save();

    // 30 days
    testTime()->freeze(Carbon::now()->addDays(30));
    $mock = $this->partialMock(FormSubmissionCleaner::class, function (MockInterface $mock) {
        $mock
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('cleanupSubmission')->times(0);
    });
    $mock->cleanup();
});

//
// CLEANUP SUBMISSION
//
it('correctly preserves any assets when delete_assets false', function () {
    // supporting components
    $assetContainer = (new AssetContainer)
        ->title('Test Container')
        ->handle('assets')
        ->disk('assets')
        ->save();

    $tmpFile = tempnam(sys_get_temp_dir(), 'test_asset.png');
    copy(__DIR__.'/../__fixtures__/assets/mity.png', $tmpFile);

    $file = new UploadedFile(
        $tmpFile,
        'mity.png',
        'image/jpeg',
        null,
        true
    );

    $asset = $assetContainer->makeAsset($file->getFilename())->upload($file);

    $assetId = $asset->id();
    expect($asset->exists())->toBe(true);

    $form = Form::make('form_with_assets')
        ->title('Form with Assets');
    $form->save();

    $submission = $form->makeSubmission()
        ->data([
            'name' => 'Name',
            'another_assets' => [
                $asset->path,
            ],
        ]);
    $submission->save();

    callProtectedMethod($this->support, 'cleanupSubmission', [
        'submission' => $submission,
        'config' => [
            ...$this->support->getFormConfig()['form_with_assets'],
            'delete_assets' => false,
        ],
    ]);

    expect(Asset::findById($assetId))->not()->toBeNull();
});

it('correctly deletes any assets when delete_assets true', function () {
    // supporting components
    $assetContainer = (new AssetContainer)
        ->title('Test Container')
        ->handle('assets')
        ->disk('assets')
        ->save();

    $tmpFile = tempnam(sys_get_temp_dir(), 'test_asset.png');
    copy(__DIR__.'/../__fixtures__/assets/mity.png', $tmpFile);

    $file = new UploadedFile(
        $tmpFile,
        'mity.png',
        'image/jpeg',
        null,
        true
    );

    $asset = $assetContainer->makeAsset($file->getFilename())->upload($file);

    $assetId = $asset->id();
    expect($asset->exists())->toBe(true);

    $form = Form::make('form_with_assets')
        ->title('Form with Assets');
    $form->save();

    $submission = $form->makeSubmission()
        ->data([
            'name' => 'Name',
            'another_assets' => [
                $asset->path,
            ],
        ]);
    $submission->save();

    callProtectedMethod($this->support, 'cleanupSubmission', [
        'submission' => $submission,
        'config' => $this->support->getFormConfig()['form_with_assets'],
    ]);

    expect(Asset::findById($assetId))->toBeNull();
});

it('correctly deletes the submission when delete_submissions true', function () {
    $form = Form::make('form_a')
        ->title('Form A');
    $form->save();

    $submission = $form->makeSubmission()
        ->data(['name' => 'Name']);
    $submission->save();

    expect($form->submission($submission->id()))->toBeInstanceOf(Submission::class);

    callProtectedMethod($this->support, 'cleanupSubmission', [
        'submission' => $submission,
        'config' => [
            'delete_submissions' => true,
        ],
    ]);

    expect($form->submission($submission->id()))->toBeNull();
});

it('correctly preserves the submission when delete_submissions true', function () {
    $form = Form::make('form_a')
        ->title('Form A');
    $form->save();

    $submission = $form->makeSubmission()
        ->data(['name' => 'Name']);
    $submission->save();

    expect($form->submission($submission->id()))->toBeInstanceOf(Submission::class);

    callProtectedMethod($this->support, 'cleanupSubmission', [
        'submission' => $submission,
        'config' => [
            'delete_submissions' => false,
        ],
    ]);

    expect($form->submission($submission->id()))->toBeInstanceOf(Submission::class);
});

class TestScope extends Scope
{
    public function apply($query, $params) {}
}

class AnotherTestScope extends Scope
{
    public function apply($query, $params) {}
}
