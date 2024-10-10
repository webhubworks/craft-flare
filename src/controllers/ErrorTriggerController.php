<?php

namespace webhubworks\flare\controllers;

use craft\web\Controller;
use webhubworks\flare\CraftFlare;

class ErrorTriggerController extends Controller
{
    protected array|int|bool $allowAnonymous = ['trigger-error'];

    public function actionTriggerError(): string
    {
        // Get the request object
        $request = \Craft::$app->request;

        // Accessing fields from POST request (if submitting form data)
        $type = $request->getBodyParam('type');

        try {
            // trigger an error
            if ($type === 'handled_exception') {
                throw new \Exception('This is a handled test exception for the craft-flare plugin');
            }
        } catch (\Exception $exception) {
            $flare = CraftFlare::getFlareInstance();
            $flare->reportHandled($exception);

            return "Exception has been reported to Flare. 🔥";
        }

        if ($type === 'unhandled_exception') {
            throw new \Exception('This is an unhandled test exception for the craft-flare plugin');
        }

        return "Type $type is not supported.";
    }
}
