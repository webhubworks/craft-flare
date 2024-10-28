<?php

namespace webhubworks\flare;

use Craft;
use craft\base\Plugin;
use craft\events\ExceptionEvent;
use craft\events\PluginEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\App;
use craft\models\UserGroup;
use craft\services\Plugins;
use craft\web\ErrorHandler;
use craft\web\UrlManager;
use Spatie\FlareClient\Flare;
use Throwable;
use webhubworks\flare\models\Settings;
use yii\base\Event;
use yii\web\NotFoundHttpException;

/**
 * Craft Flare plugin
 *
 * @method static CraftFlare getInstance()
 * @author webhub GmbH
 * @copyright webhub GmbH
 * @license MIT
 */
class CraftFlare extends Plugin
{
    public string $schemaVersion = '1.0.0';

    private static $flareInstance;

    public function createSettingsModel(): Settings
    {
        return new Settings();
    }

    public function init(): void
    {
        parent::init();

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['craft-flare/errors/trigger-error'] = 'craft-flare/error-trigger/trigger-error';
            }
        );

        $this->setupFlare();

        Event::on(
            ErrorHandler::class,
            ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
            function (ExceptionEvent $event) {
                self::$flareInstance?->report($event->exception);
            }
        );
        
        Event::on(
            Plugins::class,
            Plugins::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS,
            function (PluginEvent $event) {
                if($event->plugin->id !== 'craft-flare') {
                    return;
                }

                $settings = $event->plugin->getSettings();
                $settings->censorRequestBodyFields = $settings->setCensorRequestBodyFields($event->plugin->getSettings()->censorRequestBodyFields);
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

    public static function getFlareInstance(): ?Flare
    {
        return self::$flareInstance;
    }

    private function setupFlare(): void
    {
        if (self::$flareInstance) {
            return;
        }

        if ($this->getSettings()->isEnabled !== true) {
            return;
        }

        $flareApiToken = App::parseEnv($this->getSettings()->flareKey);

        if (! $flareApiToken) {
            return;
        }

        self::$flareInstance = Flare::make($flareApiToken)->registerFlareHandlers();

        if ($this->getSettings()->anonymizeIp) {
            self::$flareInstance = self::$flareInstance->anonymizeIp();
        }

        self::$flareInstance
            ->censorRequestBodyFields($this->getSettings()->censorRequestBodyFields)
            ->reportErrorLevels($this->getSettings()->reportErrorLevels)
            ->setStage(App::env('CRAFT_ENVIRONMENT'))
            ->filterExceptionsUsing(fn (Throwable $throwable) => ! $throwable instanceof NotFoundHttpException);

        self::$flareInstance->context('Craft CMS', [
            'version' => Craft::$app->getVersion(),
            'edition' => Craft::$app->getEdition(),
            'isMultiSite' => Craft::$app->getIsMultiSite(),
            'isCpRequest' => Craft::$app->getRequest()->getIsCpRequest(),
            'isSiteRequest' => Craft::$app->getRequest()->getIsSiteRequest(),
            'isLivePreview' => Craft::$app->getRequest()->getIsLivePreview(),
            'isActionRequest' => Craft::$app->getRequest()->getIsActionRequest(),
            'isSecureConnection' => ! Craft::$app->getRequest()->getIsConsoleRequest() && Craft::$app->getRequest()->getIsSecureConnection(),
        ]);

        self::$flareInstance->context('Plugins', [
            'enabled' => array_map(fn (Plugin $plugin) => $plugin->handle, Craft::$app->getPlugins()->getAllPlugins()),
        ]);

        $user = Craft::$app->getUser()->getIdentity();

        if (is_null($user)) {
            self::$flareInstance->context('User', 'Guest');
        }

        if ($user) {
            $groups = array_map(fn (UserGroup $group) => $group->name, $user->getGroups());

            self::$flareInstance->context('User', [
                'id' => $user->id,
                'groups' => $groups,
                'is_admin' => $user->admin,
                'language' => $user->preferredLanguage,
                'locale' => $user->preferredLocale,
            ]);
        }
    }
}
