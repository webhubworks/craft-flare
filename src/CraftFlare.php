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

    public function createSettingsModel(): Settings
    {
        return new Settings();
    }

    public function init(): void
    {
        parent::init();

        Event::on(ErrorHandler::class, ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION, function (ExceptionEvent $event) {

            if (App::parseBooleanEnv('$FLARE_ENABLED') !== true) {
                return;
            }

            $flareApiToken = App::parseEnv('$FLARE_KEY');

            if (! $flareApiToken) {
                return;
            }

            $flare = Flare::make($flareApiToken);

            if ($this->getSettings()->anonymizeIp) {
                $flare = $flare->anonymizeIp();
            }

            $flare->censorRequestBodyFields($this->getSettings()->getCensorRequestBodyFields())
                ->reportErrorLevels($this->getSettings()->reportErrorLevels)
                ->setStage(App::env('FLARE_STAGE') ?? App::env('CRAFT_ENVIRONMENT'))
                ->filterExceptionsUsing(fn (Throwable $throwable) => ! $throwable instanceof NotFoundHttpException);

            $flare->context('Craft CMS', [
                'version' => Craft::$app->getVersion(),
                'edition' => Craft::$app->getEdition(),
                'isMultiSite' => Craft::$app->getIsMultiSite(),
                'isCpRequest' => Craft::$app->getRequest()->getIsCpRequest(),
                'isSiteRequest' => Craft::$app->getRequest()->getIsSiteRequest(),
                'isLivePreview' => Craft::$app->getRequest()->getIsLivePreview(),
                'isActionRequest' => Craft::$app->getRequest()->getIsActionRequest(),
                'isSecureConnection' => Craft::$app->getRequest()->getIsSecureConnection(),
            ]);

            $flare->context('Plugins', [
                'enabled' => array_map(fn (Plugin $plugin) => $plugin->handle, Craft::$app->getPlugins()->getAllPlugins()),
            ]);

            $user = Craft::$app->getUser()->getIdentity();

            if (is_null($user)) {
                $flare->context('User', 'Guest');
            }

            if ($user) {
                $groups = array_map(fn (UserGroup $group) => $group->name, $user->getGroups());

                $flare->context('User', [
                    'id' => $user->id,
                    'groups' => $groups,
                    'is_admin' => $user->admin,
                    'language' => $user->preferredLanguage,
                    'locale' => $user->preferredLocale,
                ]);
            }

            $flare->report($event->exception);
        });
    }
}
