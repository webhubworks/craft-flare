<?php

namespace webhubworks\flare\models;

use craft\base\Model;

class Settings extends Model
{
    public string $flareKey = '';

    public bool $anonymizeIp = true;

    public array $censorRequestBodyFields = [];

    public int $reportErrorLevels = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING;

    public bool $useDefaultCensorRequestBodyFields = true;

    private array $defaultCensorRequestBodyFields = [
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
