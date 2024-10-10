<?php

namespace webhubworks\flare\models;

use craft\base\Model;

class Settings extends Model
{
    public string $flareKey = '';

    public bool $anonymizeIp = true;

    public array $censorRequestBodyFields = [];

    /**
     * Will send all errors except E_NOTICE, E_DEPRECATED and E_WARNING errors
     * -> Results into "reportErrorLevels: 24565"
     *
     * Therefore, fatal errors, parse errors, and other critical errors will still be reported.
     * Specifically, these include:
     * E_ERROR (Fatal run-time errors)
     * E_PARSE (Compile-time parse errors)
     * E_CORE_ERROR (Fatal errors occurring during PHP's initial startup)
     * E_COMPILE_ERROR (Fatal compile-time errors)
     * E_USER_ERROR (User-generated error message)
     */
    public int $reportErrorLevels = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING;

    public bool $useDefaultCensorRequestBodyFields = true;

    private array $defaultCensorRequestBodyFields = [
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
        'username',
    ];

    public function getCensorRequestBodyFields(): array
    {
        return $this->useDefaultCensorRequestBodyFields ?
            array_merge($this->defaultCensorRequestBodyFields, $this->censorRequestBodyFields) :
            $this->censorRequestBodyFields;
    }

    public function defineRules(): array
    {
        return [
            [['anonymizeIp', 'reportErrorLevels'], 'required'],
            ['anonymizeIp', 'boolean'],
            ['reportErrorLevels', 'integer'],
            ['censorRequestBodyFields', 'each', 'rule' => ['string']],
        ];
    }
}
