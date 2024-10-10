<?php

namespace webhubworks\flare;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\ExceptionEvent;
use craft\helpers\App;
use craft\models\UserGroup;
use craft\web\ErrorHandler;
use Spatie\FlareClient\Flare;
use Throwable;
use webhubworks\flare\models\Settings;
use yii\base\Event;
use yii\web\NotFoundHttpException;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;

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
                self::$flareInstance->report($event->exception);
            }
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

        if (App::parseBooleanEnv('$FLARE_ENABLED') !== true) {
            return;
        }

        $flareApiToken = App::parseEnv('$FLARE_KEY');

        if (! $flareApiToken) {
            return;
        }

        self::$flareInstance = Flare::make($flareApiToken)
            ->registerFlareHandlers();

        if ($this->getSettings()->anonymizeIp) {
            self::$flareInstance = self::$flareInstance->anonymizeIp();
        }

        self::$flareInstance->censorRequestBodyFields($this->getSettings()->getCensorRequestBodyFields())
            ->reportErrorLevels($this->getSettings()->reportErrorLevels)
            ->setStage(App::env('FLARE_STAGE') ?? App::env('CRAFT_ENVIRONMENT'))
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
