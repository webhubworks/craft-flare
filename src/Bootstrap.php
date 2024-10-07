<?php

namespace webhubworks\flare;

use yii\base\Application;
use yii\base\BootstrapInterface;

class Bootstrap implements BootstrapInterface
{
    /**
     * Installs our components during the bootstrap process to get us loaded
     * sooner in case something crashes.
     *
     * @param Application|\craft\web\Application $app
     */
    public function bootstrap($app): void
    {
        $app->getPlugins()->getPlugin('craft-flare');
    }
}
