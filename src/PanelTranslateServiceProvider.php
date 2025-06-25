<?php

namespace SmartCms\PanelTranslate;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PanelTranslateServiceProvider extends PackageServiceProvider
{
    public static string $name = 'panel-translate';

    public static string $viewNamespace = 'panel-translate';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()->hasTranslations();
    }
}
