<?php

namespace SmartCms\PanelTranslate;

use Filament\Contracts\Plugin;
use Filament\Panel;

class PanelTranslatePlugin implements Plugin
{
    public static ?string $navigationGroup = null;

    final public function __construct(?string $navigationGroup)
    {
        static::$navigationGroup = $navigationGroup;
    }

    public function getId(): string
    {
        return 'panel-translate';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([TranslatesPage::class]);
    }

    public function boot(Panel $panel): void {}

    public static function make(?string $navigationGroup = null): static
    {
        return app(static::class, ['navigationGroup' => $navigationGroup]);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
