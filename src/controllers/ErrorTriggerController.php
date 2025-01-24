<?php

namespace webhubworks\flare\controllers;

use craft\web\Controller;
use webhubworks\flare\CraftFlare;

class ErrorTriggerController extends Controller
{
    protected array|int|bool $allowAnonymous = ['trigger-error'];
    
    public function actionTriggerError(): string
    {
        $type = \Craft::$app->request->getBodyParam('type');
        
        switch ($type) {
            case 'handled':
                try {
                    throw new \Exception('This is a handled test exception thrown by the Craft Flare plugin.');
                    
                } catch (\Exception $exception) {
                    CraftFlare::getFlareInstance()->reportHandled($exception);
                    
                    return "The exception has been reported to Flare. 🔥";
                }
                break;
            
            case 'unhandled':
                throw new \Exception('This is an unhandled test exception thrown by the Craft Flare plugin.');
                break;
            
            default:
                return "The type '$type' is not supported.";
        }
    }
}
