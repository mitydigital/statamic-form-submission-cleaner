<?php

use MityDigital\StatamicFormSubmissionCleaner\Console\Commands\RunCommand;
use MityDigital\StatamicFormSubmissionCleaner\Facades\FormSubmissionCleaner;

beforeEach(function () {
    $this->command = app(RunCommand::class);
});

it('has the correct signature', function () {
    $signature = getPrivateProperty(RunCommand::class, 'signature');

    expect($signature->getValue($this->command))->toBe('statamic:form-submission-cleaner:run');
});

it('calls the cleanup method', function () {
    FormSubmissionCleaner::shouldReceive('cleanup')->once();

    $this->artisan('statamic:form-submission-cleaner:run');
});
