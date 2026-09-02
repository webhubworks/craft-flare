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
    /**
     * How many levels of nesting the censored body fields are expanded to.
     *
     * Craft posts custom field data as `fields[handle]`, so a bare field name never matches.
     * Each extra level multiplies the work the redactor does on a large request body, so the
     * expansion stops at the depth that covers Craft's own field naming.
     */
    private const CENSOR_FIELD_DEPTH = 2;

    /**
     * Credential fields that are censored whatever the `censorRequestBodyFields` setting says.
     *
     * The client already forces `password` and `password_confirmation` on top of the configured
     * list. These are the Craft equivalents, so a project that installed the plugin before a
     * field was added to the defaults still does not post credentials to Flare.
     */
    private const ALWAYS_CENSORED_BODY_FIELDS = [
        'CRAFT_CSRF_TOKEN',
        'password',
        'newPassword',
        'currentPassword',
        'account-password',
        'loginName',
    ];

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
            // `Flare::make()` only applies the client defaults when it is handed an API token
            // string. Passing a `FlareConfig` skips them, which leaves `$collects` empty and
            // drops every collector, including the request information middleware.
            ->useDefaults()
            // The dump recorder swaps the global `VarDumper` handler. This service is resolved
            // lazily while an exception is already being handled, so the swap happens after any
            // dump it could have recorded.
            ->ignoreDumps()
            // Collecting stack frame arguments sets `zend.exception_ignore_args` to 0 for the
            // whole process. On a production ini the exception has already been created by then,
            // so its trace carries no arguments either way, and arguments can hold credentials.
            ->ignoreStackFrameArguments()
            // Without an application path the client cannot find the repository, so no Git
            // information is collected and stack frames are not trimmed to the project.
            ->applicationPath($this->applicationPath())
            ->reportErrorLevels($settings->reportErrorLevels)
            ->applicationStage(App::env('CRAFT_ENVIRONMENT'))
            ->censorCookies($settings->censorCookies)
            ->censorBodyFields(...$this->censoredBodyFieldPaths($settings->censorRequestBodyFields))
            ->filterExceptionsUsing(function(Throwable $throwable) use ($ignoredHttpStatusCodes) {
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

        Craft::$app->onInit(function() {
            $this->addPluginContext();
            $this->addUserContext();
        });
    }

    private function applicationPath(): string
    {
        $root = Craft::getAlias('@root', false);

        return is_string($root) ? $root : dirname(Craft::getAlias('@webroot'));
    }

    /**
     * Expands each configured field name into the nested paths Craft actually posts under.
     *
     * The client matches censored body fields as dot paths from the root of the request body,
     * but Craft submits custom field data as `fields[handle]`, so a bare `email` never matches
     * `fields[email]`. Anything nested deeper than `CENSOR_FIELD_DEPTH`, such as Matrix block
     * content, needs an explicit dot path in the setting.
     *
     * @param string[] $fields
     * @return string[]
     */
    private function censoredBodyFieldPaths(array $fields): array
    {
        $paths = [];

        foreach (array_unique([...self::ALWAYS_CENSORED_BODY_FIELDS, ...$fields]) as $field) {
            $prefix = '';

            for ($depth = 0; $depth <= self::CENSOR_FIELD_DEPTH; $depth++) {
                $paths[] = $prefix . $field;
                $prefix .= '*.';
            }
        }

        return $paths;
    }

    public function getClient(): ?Flare
    {
        return $this->client;
    }

    private function addPluginContext(): void
    {
        if ($this->client === null) {
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
        if ($this->client === null) {
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
