<?php

namespace webhubworks\flare;

use craft\console\Application as ConsoleApplication;
use craft\web\Application as WebApplication;
use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
    /**
     * Installs our components during the bootstrap process to get us loaded
     * sooner in case something crashes.
     *
     * @param WebApplication|ConsoleApplication $app
     */
    public function bootstrap($app): void
    {
        $app->getPlugins()->getPlugin('craft-flare');
    }
}
