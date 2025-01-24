<?php

namespace webhubworks\flare\controllers;

use craft\web\Controller;
use webhubworks\flare\CraftFlare;
use webhubworks\flare\exceptions\HandledCraftFlareTestException;
use webhubworks\flare\exceptions\UnhandledCraftFlareTestException;

class TestErrorTrackingController extends Controller
{
    /**
     * @throws UnhandledCraftFlareTestException
     */
    public function actionThrowTestException(): string
    {
        $type = \Craft::$app->request->getBodyParam('type');
        
        switch ($type) {
            case 'handled':
                try {
                    throw new HandledCraftFlareTestException();
                    
                } catch (HandledCraftFlareTestException $exception) {
                    CraftFlare::getFlareInstance()->reportHandled($exception);
                    
                    return "The exception has been reported to Flare. 🔥";
                }
            
            case 'unhandled':
                throw new UnhandledCraftFlareTestException();
            
            default:
                return "The type '$type' is not supported.";
        }
    }
}
