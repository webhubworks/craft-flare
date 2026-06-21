<?php

namespace webhubworks\flare\services;

use Craft;
use craft\base\Component;
use craft\base\Plugin;
use craft\helpers\App;
use craft\models\UserGroup;
use Spatie\FlareClient\Flare;
use Throwable;
use webhubworks\flare\CraftFlare;
use webhubworks\flare\middleware\CensorQueriesMiddleware;
use webhubworks\flare\middleware\RemoveAllRequestIp;
use yii\web\HttpException;

class FlareService extends Component
{
    private ?Flare $client = null;

    /**
     * @throws Throwable
     */
    public function __construct()
    {
        parent::__construct();
        
        $settings = CraftFlare::getInstance()->getSettings();

        if ($settings->isEnabled !== true) {
            return;
        }

        $flareApiToken = App::parseEnv($settings->flareKey);

        if (!$flareApiToken) {
            return;
        }

        $this->client = Flare::make($flareApiToken)->registerFlareHandlers();

        if ($settings->anonymizeIp) {
            $this->client->anonymizeIp();
            $this->client->registerMiddleware(RemoveAllRequestIp::class);
        }

        if ($settings->censorQueries) {
            $this->client->registerMiddleware(CensorQueriesMiddleware::class);
        }

        $ignoredHttpStatusCodes = $settings->ignoredHttpStatusCodes;

        $this->client
            ->censorRequestBodyFields($settings->censorRequestBodyFields)
            ->reportErrorLevels($settings->reportErrorLevels)
            ->setStage(App::env('CRAFT_ENVIRONMENT'))
            ->filterExceptionsUsing(function (Throwable $throwable) use ($ignoredHttpStatusCodes) {
                // Unwrap Twig runtime errors that merely re-wrap an HTTP exception
                // (e.g. `{% exit 403 %}`) so we can inspect the underlying status code.
                $exception = $throwable instanceof \Twig\Error\RuntimeError
                    ? $throwable->getPrevious()
                    : $throwable;

                // Filter out HTTP exceptions whose status code is configured as ignored.
                // Matching on the status code (rather than the exception class) also catches
                // generic `HttpException(403|404|...)` throws from third-party code - e.g.
                // verbb/wishlist throws a plain `HttpException(403)`, not `ForbiddenHttpException`.
                if ($exception instanceof HttpException
                    && in_array($exception->statusCode, $ignoredHttpStatusCodes, true)) {
                    return false;
                }

                return true;
            });

        $this->client->context('Craft CMS', [
            'version' => Craft::$app->getVersion(),
            'edition' => Craft::$app->getEdition(),
            'isMultiSite' => Craft::$app->getIsMultiSite(),
            'isCpRequest' => Craft::$app->getRequest()->getIsCpRequest(),
            'isSiteRequest' => Craft::$app->getRequest()->getIsSiteRequest(),
            'isLivePreview' => Craft::$app->getRequest()->getIsLivePreview(),
            'isActionRequest' => Craft::$app->getRequest()->getIsActionRequest(),
            'isSecureConnection' => !Craft::$app->getRequest()->getIsConsoleRequest() && Craft::$app->getRequest()->getIsSecureConnection(),
        ]);

        $this->client->context('User', 'Craft not initialized yet');

        Craft::$app->onInit(function () {
            $this->addPluginContext();
            $this->addUserContext();
        });
    }

    public function getClient(): ?Flare
    {
       return $this->client;
    }

    private function addPluginContext(): void
    {
        if($this->client === null) {
            return;
        }

        $this->client->context('Plugins', [
            'enabled' => array_map(fn(Plugin $plugin) => $plugin->handle, Craft::$app->getPlugins()->getAllPlugins()),
        ]);
    }

    /**
     * Watch out: Craft or plugins might not be fully initialized at this point.
     * See: https://craftcms.com/docs/5.x/extend/plugin-guide.html#initialization
     *
     * @return void
     * @throws Throwable
     */
    private function addUserContext(): void
    {
        if($this->client === null) {
            return;
        }

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->client->context('User', 'Console');
            return;
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (is_null($user)) {
            $this->client->context('User', 'Guest');
        }

        if ($user) {
            $groups = array_map(fn(UserGroup $group) => $group->name, $user->getGroups());

            $this->client->context('User', [
                'id' => $user->id,
                'groups' => $groups,
                'is_admin' => $user->admin,
                'language' => $user->preferredLanguage,
                'locale' => $user->preferredLocale,
            ]);
        }
    }
}
