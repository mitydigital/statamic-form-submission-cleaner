<?php

namespace MityDigital\StatamicFormSubmissionCleaner\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static int cleanup()
 * @method static Collection getFormConfig()
 *
 * @see \MityDigital\StatamicFormSubmissionCleaner\Support\FormSubmissionCleaner
 */
class FormSubmissionCleaner extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'formSubmissionCleaner';
    }
}
