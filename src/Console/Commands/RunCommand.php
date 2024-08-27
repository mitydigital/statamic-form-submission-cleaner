<?php

namespace MityDigital\StatamicFormSubmissionCleaner\Console\Commands;

use Illuminate\Console\Command;
use MityDigital\StatamicFormSubmissionCleaner\Facades\FormSubmissionCleaner;
use Statamic\Console\RunsInPlease;

class RunCommand extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:form-submission-cleaner:run';

    protected $description = 'Cleans up and removes old form submissions.';

    public function handle(): void
    {
        $this->info(__('statamic-form-submission-cleaner::command.starting'));
        
        $cleaned = FormSubmissionCleaner::cleanup();

        $this->info(__('statamic-form-submission-cleaner::command.completed', [
            'cleaned' => $cleaned,
            'submission' => $cleaned === 1 ? __('Submission') : __('Submissions'),
        ]));
    }
}
