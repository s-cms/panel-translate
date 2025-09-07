<?php

namespace SmartCms\PanelTranslate\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class ScanTranslates
{
    public static function run(): Collection
    {
        return (new self)->handle();
    }

    public function handle(): Collection
    {
        $translations = [];

        $bladeTranslations = $this->scanBladeFiles();
        $translations = array_merge($translations, $bladeTranslations);

        $fileTranslations = $this->scanTranslationFiles();
        $translations = array_merge($translations, $fileTranslations);

        $fullTranslations = $this->getFullTranslationData($translations);
        $collections = [];
        foreach ($fullTranslations as $key => $value) {
            $collections[] = [
                'key' => $key,
                'values' => $value,
                'count' => count(array_filter($value, fn($item) => ! empty($item) && strlen($item) > 0)),
            ];
        }
        return collect($collections);
    }

    private function scanBladeFiles(): array
    {
        $path = resource_path('views');
        $files = File::allFiles($path);
        $regex = '/__\(\s*[\'"](.+?)[\'"]\s*\)/';
        $translations = [];

        foreach ($files as $file) {
            $contents = File::get($file->getRealPath());
            if (preg_match_all($regex, $contents, $matches)) {
                foreach ($matches[1] as $key) {
                    $translations[$key] = $key;
                }
            }
        }

        return $translations;
    }

    private function scanTranslationFiles(): array
    {
        $translations = [];
        $locales = $this->getAvailableLanguages();

        foreach ($locales as $locale) {
            $jsonFile = resource_path('lang') . '/' . $locale . '.json';
            if (File::exists($jsonFile)) {
                $content = File::get($jsonFile);
                $data = json_decode($content, true);
                if (is_array($data)) {
                    foreach (array_keys($data) as $key) {
                        $translations[$key] = $key;
                    }
                }
            }
        }

        return $translations;
    }

    private function getFullTranslationData(array $keys): array
    {
        $translations = [];
        $locales = $this->getAvailableLanguages();

        foreach ($keys as $key) {
            $values = [];

            foreach ($locales as $locale) {
                $jsonFile = resource_path('lang') . '/' . $locale . '.json';
                $value = '';

                if (File::exists($jsonFile)) {
                    $content = File::get($jsonFile);
                    $data = json_decode($content, true);
                    if (is_array($data) && isset($data[$key])) {
                        $value = $data[$key];
                    }
                }

                $values[$locale] = $value;
            }

            $translations[$key] = $values;
        }

        return $translations;
    }

    /**
     * Get available languages by scanning both JSON files and language directories
     */
    private function getAvailableLanguages(): array
    {
        $locales = [];
        $langPath = resource_path('lang');

        if (!File::exists($langPath)) {
            return ['en', 'ru']; // Fallback to default locales if lang directory doesn't exist
        }

        // Scan for JSON files (e.g., en.json, ru.json)
        $jsonFiles = File::glob($langPath . '/*.json');
        foreach ($jsonFiles as $jsonFile) {
            $locale = basename($jsonFile, '.json');
            $locales[] = $locale;
        }

        // Scan for language directories (e.g., en/, ru/)
        $directories = File::directories($langPath);
        foreach ($directories as $directory) {
            $locale = basename($directory);
            $locales[] = $locale;
        }

        // Remove duplicates and sort
        $locales = array_unique($locales);
        sort($locales);

        // Return default locales if no languages found
        return empty($locales) ? ['en', 'ru'] : $locales;
    }

    public function asCommand(Command $command): void
    {
        $translations = $this->handle();
        $availableLanguages = $this->getAvailableLanguages();

        $command->info('Available languages: ' . implode(', ', $availableLanguages));
        $command->info('Found ' . $translations->count() . ' translation keys.');

        foreach ($translations as $item) {
            $command->line("Key: {$item['key']}");
            foreach ($item['values'] as $locale => $value) {
                $command->line("  {$locale}: {$value}");
            }
            $command->line('');
        }

        $command->info('Translations scanned and saved.');
    }
}
