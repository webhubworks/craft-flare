<?php

namespace webhubworks\flare\controllers;

use craft\web\Controller;
use webhubworks\flare\CraftFlare;
use webhubworks\flare\exceptions\HandledCraftFlareTestException;
use webhubworks\flare\exceptions\UnhandledCraftFlareTestException;
use yii\web\Response;

class TestErrorTrackingController extends Controller
{
    /**
     * @throws UnhandledCraftFlareTestException
     */
    public function actionThrowTestException(): Response
    {
        $type = $this->request->getBodyParam('exceptionType');
        
        switch ($type) {
            case 'handled':
                try {
                    throw new HandledCraftFlareTestException();
                    
                } catch (HandledCraftFlareTestException $exception) {
                    CraftFlare::getFlareInstance()->reportHandled($exception);
                    
                    return $this->asJson([
                        'message' => 'The exception was handled and reported to Flare.'
                    ])->setStatusCode(200);
                }
            
            case 'unhandled':
                throw new UnhandledCraftFlareTestException();
            
            default:
                return $this->asJson([
                    'message' => "The exception type '$type' is not supported by the testing feature."
                ])->setStatusCode(400);
        }
    }
}
