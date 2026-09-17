<?php

namespace AmjadIqbal\VueForge;

use System\Classes\PluginBase;
use AmjadIqbal\VueForge\FormWidgets\VueWidget;
use AmjadIqbal\VueForge\Console\MakeVueWidget;

class Plugin extends PluginBase
{
    public function pluginDetails()
    {
        return [
            'name' => 'VueForge',
            'description' => 'The rapid Vue 3 component & widget engine for October CMS backend interfaces.',
            'author' => 'Amjad Iqbal',
            'icon' => 'icon-cubes',
            'homepage' => 'https://github.com/amjadiqbal/oc-vueforge-plugin',
        ];
    }

    public function registerFormWidgets()
    {
        return [
            VueWidget::class => [
                'label' => 'Vue Component',
                'code' => 'vueforge',
            ],
        ];
    }

    public function register()
    {
        $this->registerConsoleCommand('vueforge:make', MakeVueWidget::class);
    }
}
