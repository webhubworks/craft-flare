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
use Spatie\FlareClient\Flare;
use webhubworks\flare\models\Settings;
use webhubworks\flare\services\FlareService;
use yii\base\Event;

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

        Event::on(
            ErrorHandler::class,
            ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
            function (ExceptionEvent $event) {
                $this->flare?->report($event->exception);
            }
        );

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

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate(
            'craft-flare/settings',
            [
                'settings' => $this->getSettings(),
            ]
        );
    }
}