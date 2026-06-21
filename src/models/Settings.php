<?php

namespace webhubworks\flare\models;

use craft\base\Model;

class Settings extends Model
{
    public string $flareKey = '';

    public bool $isEnabled = true;

    public bool $anonymizeIp = true;

    public bool $censorQueries = true;

    public array $censorRequestBodyFields = [
        'CRAFT_CSRF_TOKEN',
        'password',
        'newPassword',
        'currentPassword',
        'account-password',
        'email',
        'firstName',
        'lastName',
        'fullName',
        'name',
        'username'
    ];

    /**
     * HTTP status codes of exceptions that should NOT be reported to Flare.
     *
     * Any thrown `yii\web\HttpException` (or a `Twig\Error\RuntimeError` wrapping one,
     * e.g. `{% exit 403 %}`) whose status code is listed here is filtered out. Matching
     * happens on the status code rather than the exception class, so generic
     * `HttpException(403)` / `HttpException(404)` throws from third-party code
     * (e.g. verbb/wishlist) are caught too, not just `ForbiddenHttpException` /
     * `NotFoundHttpException`.
     *
     * Defaults to client errors that are expected during normal operation (bots,
     * crawlers, link scanners, expired sessions). Add codes like 400, 401, 405 or 429
     * if a project wants to silence those as well - but be aware that doing so also
     * hides genuine signals (e.g. CSRF failures are 400, rate limiting is 429).
     *
     * @var int[]
     */
    public array $ignoredHttpStatusCodes = [403, 404];

    /**
     * Will send all errors except E_NOTICE, E_DEPRECATED, E_USER_DEPRECATED and E_WARNING errors
     * -> Results into "reportErrorLevels: 8181"
     *
     * Therefore, fatal errors, parse errors, and other critical errors will still be reported.
     * Specifically, these include:
     * E_ERROR (Fatal run-time errors)
     * E_PARSE (Compile-time parse errors)
     * E_CORE_ERROR (Fatal errors occurring during PHP's initial startup)
     * E_COMPILE_ERROR (Fatal compile-time errors)
     * E_USER_ERROR (User-generated error message)
     */
    public int $reportErrorLevels = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_WARNING;

    public function defineRules(): array
    {
        return [
            [['flareKey', 'isEnabled', 'anonymizeIp', 'censorQueries', 'censorRequestBodyFields'], 'required'],
            ['isEnabled', 'boolean'],
            ['isEnabled', 'boolean'],
            ['censorRequestBodyFields', 'safe'],
            ['ignoredHttpStatusCodes', 'safe'],
        ];
    }

    public function getCensorRequestBodyFields($value)
    {
        return array_map(function ($field) {
            return ['fieldName' => $field];
        }, $value);
    }

    public function setCensorRequestBodyFields($value)
    {
        return array_map(function ($field) {
            return $field['fieldName'];
        }, $value);
    }
}
