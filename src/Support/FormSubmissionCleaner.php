<?php

namespace MityDigital\StatamicFormSubmissionCleaner\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Statamic\Facades\Asset;
use Statamic\Facades\Form as FormFacade;
use Statamic\Facades\FormSubmission;
use Statamic\Fields\Field;
use Statamic\Forms\Form;
use Statamic\Forms\Submission;
use Statamic\Support\Arr;

class FormSubmissionCleaner
{
    public function cleanup(): int
    {
        return $this->getFormConfig()
            ->map(function (array $config, string $handle) {
                if (Arr::get($config, 'delete_assets', true) || Arr::get($config, 'delete_submissions', true)) {
                    $submissions = FormSubmission::query()
                        ->where('form', $handle)
                        ->where('date', '<', Carbon::now()->subDays($config['days']))
                        ->where(fn ($query) => $this->scopes($config)->each->apply($query, [
                            'form' => $handle,
                        ]))
                        ->get();

                    $submissions->each(fn (Submission $submission) => $this->cleanupSubmission($submission, $config));

                    return $submissions->count();
                } else {
                    return 0;
                }
            })
            ->sum();
    }

    public function getFormConfig(): Collection
    {
        // get all forms
        $forms = FormFacade::all()->mapWithKeys(fn (Form $form) => [$form->handle => $form]);

        $configOverrides = collect([]);

        // update available forms to only be those that have configuration
        if ($configuredForms = config('statamic-form-submission-cleaner.forms', 'all')) {
            if ($configuredForms != 'all') {
                $configuredForms = Arr::wrap($configuredForms);

                $handles = collect([]);
                foreach ($configuredForms as $handle => $form) {
                    if (is_array($form)) {
                        $handles->push($handle);
                        $configOverrides->put($handle, $form);
                    } else {
                        $handles->push($form);
                    }
                }

                $forms = $forms->filter(fn (Form $form, string $handle) => $handles->contains($handle));
            }
        }

        return $forms->mapWithKeys(function (Form $form, string $handle) use ($configOverrides) {
            // set base defaults
            $options = [
                'days' => config('statamic-form-submission-cleaner.defaults.days', 30),
                'delete_assets' => config('statamic-form-submission-cleaner.defaults.delete_assets', true),
                'delete_submissions' => config('statamic-form-submission-cleaner.defaults.delete_submissions', true),

                'assets_fields' => [],

                'query_scopes' => config('statamic-form-submission-cleaner.defaults.query_scopes', null),
            ];

            if ($override = $configOverrides->get($form->handle)) {
                foreach ($override as $key => $value) {
                    if (array_key_exists($key, $options)) {
                        $options[$key] = $value;
                    }
                }
            }

            // get the field handles that are "assets" fieldtypes
            $options['assets_fields'] = $form->blueprint()->fields()->all()
                ->filter(fn (Field $field) => $field->type() === 'assets');

            return [$handle => $options];
        });
    }

    protected function scopes(array $config): Collection
    {
        $scopes = Arr::get($config, 'query_scopes', []);
        if ($scopes === null) {
            $scopes = [];
        }

        return collect(Arr::wrap($scopes))
            ->map(fn ($scope) => app($scope));
    }

    protected function cleanupSubmission(Submission $submission, array $config): void
    {
        // should we delete assets?
        if (Arr::get($config, 'delete_assets', true)) {
            Arr::get($config, 'assets_fields', collect([]))
                ->each(function (Field $field) use ($submission) {
                    if ($assets = $submission->get($field->handle())) {
                        Asset::query()
                            ->where('container', $field->config()['container'])
                            ->whereIn('path', $assets)
                            ->get()
                            ->each(fn (\Statamic\Assets\Asset $asset) => $asset->delete());
                    }
                });
        }

        // should we delete the submission?
        if (Arr::get($config, 'delete_submissions', true)) {
            $submission->delete();
        }
    }
}
