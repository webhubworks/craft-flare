<?php
/**
 * Flare plugin for Craft CMS 4.x & 5.x
 *
 * Integrate Flare into Craft CMS.
 *
 * @link      https://webhub.de
 * @copyright Copyright (c) 2024 webhub GmbH
 */

/**
 * Flare config.php
 *
 * This file exists only as a template for the Flare settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'craft-flare.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    '*' => [
        // With this setting enabled, we will be sending exceptions to Flare.
        //'isEnabled' => true,

        // The Flare API key for your project.
        //'flareKey' => 'XXX',

        // With this setting enabled, the IP address of the request will be anonymized before being sent to Flare.
        //'anonymizeIp' => true,

        // With this setting you may define which request fields should be censored before being sent to Flare.
        //'censorRequestBodyFields' => [
        //    'CRAFT_CSRF_TOKEN',
        //    'password',
        //    'newPassword',
        //    'currentPassword',
        //    'account-password',
        //    'email',
        //    'firstName',
        //    'lastName',
        //    'fullName',
        //    'name',
        //    'username'
        //],
    ],
];