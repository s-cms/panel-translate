<?php

namespace SmartCms\PanelTranslate\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class ScanTranslates
{
    public static function run(): Collection
    {
        return (new self())->handle();
    }

    public function handle(): Collection
    {
        $translations = [];

        // Собираем переводы из Blade файлов
        $bladeTranslations = $this->scanBladeFiles();
        $translations = array_merge($translations, $bladeTranslations);

        // Собираем существующие переводы из файлов
        $fileTranslations = $this->scanTranslationFiles();
        $translations = array_merge($translations, $fileTranslations);

        // Получаем полные данные переводов
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
        $langPath = resource_path('lang');
        $locales = ['en', 'ru']; // Добавьте другие языки по необходимости

        foreach ($locales as $locale) {
            $jsonFile = $langPath . '/' . $locale . '.json';
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
        $langPath = resource_path('lang');
        $locales = ['en', 'ru']; // Добавьте другие языки по необходимости

        foreach ($keys as $key) {
            $values = [];

            foreach ($locales as $locale) {
                $jsonFile = $langPath . '/' . $locale . '.json';
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

    public function getTranslationData(): array
    {
        $translations = [];
        $langPath = resource_path('lang');
        $locales = ['en', 'ru']; // Добавьте другие языки по необходимости

        // Сначала собираем все ключи
        $allKeys = $this->handle();

        foreach ($allKeys as $item) {
            $translationData = ['key' => $item['key']];

            foreach ($locales as $locale) {
                $translationData[$locale] = $item['values'][$locale] ?? '';
            }

            $translations[] = $translationData;
        }

        return $translations;
    }

    public function saveTranslation(string $key, array $translations): void
    {
        UpdateTranslate::run([
            'key' => $key,
            'values' => $translations,
        ]);
    }

    public function asCommand(Command $command): void
    {
        $translations = $this->handle();
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
