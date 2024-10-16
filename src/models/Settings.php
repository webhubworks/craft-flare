<?php

namespace webhubworks\flare\models;

use craft\base\Model;

class Settings extends Model
{
    public string $flareKey = '';

    public bool $isEnabled = true;

    public bool $anonymizeIp = true;

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
            [['flareKey', 'isEnabled', 'anonymizeIp', 'censorRequestBodyFields'], 'required'],
            ['isEnabled', 'boolean'],
            ['isEnabled', 'boolean'],
            ['censorRequestBodyFields', 'safe'],
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
