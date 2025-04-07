<?php

namespace webhubworks\flare;

use Craft;
use craft\base\Plugin;
use craft\events\ExceptionEvent;
use craft\events\PluginEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Plugins;
use craft\web\ErrorHandler;
use craft\web\UrlManager;
use ErrorException;
use Spatie\FlareClient\Flare;
use webhubworks\flare\models\Settings;
use webhubworks\flare\services\FlareService;
use yii\base\Event;
use yii\queue\ExecEvent;
use yii\queue\Queue;

/**
 * Craft Flare plugin
 *
 * @method static CraftFlare getInstance()
 * @property FlareService $flare
 * @author webhub GmbH
 * @copyright webhub GmbH
 * @license MIT
 */
class CraftFlare extends Plugin
{
    public string $schemaVersion = '1.0.0';

    public function createSettingsModel(): Settings
    {
        return new Settings();
    }

    public function init(): void
    {
        parent::init();

        $this->setComponents([
            'flare' => FlareService::class,
        ]);

        /**
         * Handle "normal" exceptions.
         */
        Event::on(
            ErrorHandler::class,
            ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
            function (ExceptionEvent $event) {
                $this->flare->getClient()?->report($event->exception);
            }
        );

        /**
         * Handle queue exceptions.
         */
        Event::on(
            Queue::class,
            Queue::EVENT_AFTER_ERROR,
            function (ExecEvent $event) {
                $throwable = new ErrorException(
                    $event->error->getMessage(),
                    $event->error->getCode(),
                    1,
                    $event->error->getFile(),
                    $event->error->getLine()
                );

                $this->flare->getClient()?->report($throwable);
            }
        );

        /**
         * Handle fatal errors.
         */
        register_shutdown_function(function () {
            $error = error_get_last();

            /**
             * We check, whether the error code is in our setting reportErrorLevels to be reported.
             */
            if ($error && (bool) ($this->getSettings()->reportErrorLevels & $error['type'])) {
                $throwable = new ErrorException(
                    $error['message'],
                    0,
                    $error['type'],
                    $error['file'],
                    $error['line']
                );

                $this->flare->getClient()?->sendReportsImmediately()->report($throwable);
            }
        });

        $this->registerOtherEvents();
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate(
            'craft-flare/settings',
            [
                'settings' => $this->getSettings(),
                'csrfToken' => Craft::$app->getRequest()->getCsrfToken(),
            ]
        );
    }

    private function registerOtherEvents(): void
    {
        Event::on(
            Plugins::class,
            Plugins::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS,
            function (PluginEvent $event) {
                if ($event->plugin->id !== 'craft-flare') {
                    return;
                }

                $settings = $event->plugin->getSettings();
                $settings->censorRequestBodyFields = $settings->setCensorRequestBodyFields($event->plugin->getSettings()->censorRequestBodyFields);
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['craft-flare/errors/trigger-error'] = 'craft-flare/error-trigger/trigger-error';
            }
        );
    }

    public static function getFlareInstance(): ?Flare
    {
        if(self::getInstance() === null){
            return null;
        }

        return self::getInstance()->flare->getClient();
    }
}
