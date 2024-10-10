# Craft Flare

Flare error tracker integration for Craft CMS

## Requirements

This plugin requires Craft CMS 4.5.0 or later, and PHP 8.0.2 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Craft Flare”. Then press “Install”.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require webhubworks/craft-flare

# tell Craft to install the plugin
./craft plugin/install craft-flare
```
#### Add environment variables
Add these two environment variables to your `.env` file.
```dotenv
FLARE_ENABLED=true
FLARE_KEY="XXX"
```

## Configuration
#### Bootstrapping
Installs our components during the bootstrap process to get us loaded sooner in case something crashes.

```php
# config/app.php

'bootstrap' => [
    ...
    '\webhubworks\flare\Bootstrap', <-- Add this line
],
```