<?php

namespace webhubworks\flare\services;

use Craft;
use craft\base\Component;
use craft\base\Plugin;
use craft\helpers\App;
use craft\models\UserGroup;
use Spatie\FlareClient\Flare;
use Spatie\FlareClient\FlareConfig;
use Throwable;
use webhubworks\flare\CraftFlare;
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

        $ignoredHttpStatusCodes = $settings->ignoredHttpStatusCodes;

        $config = FlareConfig::make($flareApiToken)
            ->reportErrorLevels($settings->reportErrorLevels)
            ->applicationStage(App::env('CRAFT_ENVIRONMENT'))
            ->censorBodyFields(...$settings->censorRequestBodyFields)
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

        if ($settings->anonymizeIp) {
            $config->censorClientIps();
            $config->censorHeaders(
                'x-forwarded-for',
                'x-real-ip',
                'x-request-ip',
                'x-client-ip',
                'cf-connecting-ip',
                'fastly-client-ip',
                'true-client-ip',
                'forwarded',
                'proxy-client-ip',
                'wl-proxy-client-ip',
            );
        }

        if ($settings->censorQueries) {
            $config->collectQueries(includeBindings: false);
        }

        $this->client = Flare::make($config)->registerFlareHandlers();

        $this->client->context('craft_cms', [
            'version' => Craft::$app->getVersion(),
            'edition' => Craft::$app->getEdition(),
            'is_multi_site' => Craft::$app->getIsMultiSite(),
            'is_cp_request' => Craft::$app->getRequest()->getIsCpRequest(),
            'is_site_request' => Craft::$app->getRequest()->getIsSiteRequest(),
            'is_live_preview' => Craft::$app->getRequest()->getIsLivePreview(),
            'is_action_request' => Craft::$app->getRequest()->getIsActionRequest(),
            'is_secure_connection' => !Craft::$app->getRequest()->getIsConsoleRequest() && Craft::$app->getRequest()->getIsSecureConnection(),
        ]);

        $this->client->context('user', 'Craft not initialized yet');

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

        $this->client->context('plugins', [
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
            $this->client->context('user', 'Console');
            return;
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (is_null($user)) {
            $this->client->context('user', 'Guest');
        }

        if ($user) {
            $groups = array_map(fn(UserGroup $group) => $group->name, $user->getGroups());

            $this->client->context('user', [
                'id' => $user->id,
                'groups' => $groups,
                'is_admin' => $user->admin,
                'language' => $user->preferredLanguage,
                'locale' => $user->preferredLocale,
            ]);
        }
    }
}
