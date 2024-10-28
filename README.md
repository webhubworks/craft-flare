# Craft Flare plugin for Craft CMS 4.x and 5.x

Flare error tracker integration for Craft CMS

## Requirements

This plugin requires Craft CMS 4.5.0/5.0.0 or later, and PHP 8.0.2 or later.

You'll also have to provide an Flare API key.

🙏 You can create a Flare account using our affiliate link: [Flare](flareapp.io/?via=webhub) This helps a lot supporting the maintenance of this plugin.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin.

        composer require webhubworks/craft-flare

3. Install and enable the plugin:

        ./craft plugin/install craft-flare && ./craft plugin/enable craft-flare

   OR: In the Control Panel, go to Settings → Plugins and click the “Install” button for Craft Flare.

## Configuration

Create a new PHP project in Flare or go to the settings page of your existing Flare project and copy your project specific Flare API key. Paste this key into the Craft Flare settings.

#### Bootstrapping (optional)
To load Flare as early as possible during the application boot up add the following line into your `config/app.php` file:

```php
# config/app.php

'bootstrap' => [
    ...
    '\webhubworks\flare\Bootstrap', // <-- Add this line
],
```

## Test flare
In order to quickly test whether everything is set up, you can run the following command in your console:
```bash
php craft exec/exec "throw new \Exception('This is an exception to test if the integration with Flare works.');"
```
