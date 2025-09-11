<?php

namespace SmartCms\PanelTranslate\Actions;

use Illuminate\Support\Facades\File;

class UpdateTranslate
{
    public static function run(array $data): bool
    {
        return (new self)->handle($data);
    }

    public function handle(array $data): bool
    {
        // $data = ['key' => 'test', 'values' => ['en' => 'test', 'uk' => 'test']]
        $key = $data['key'];
        $values = $data['values'];
        $langPath = resource_path('lang');

        // Create lang directory if it doesn't exist
        if (! File::exists($langPath)) {
            File::makeDirectory($langPath, 0755, true);
        }

        foreach ($values as $locale => $value) {
            $jsonFile = $langPath . '/' . $locale . '.json';
            $data = [];

            if (File::exists($jsonFile)) {
                $content = File::get($jsonFile);
                $data = json_decode($content, true) ?: [];
            }

            if (! empty($value)) {
                $data[$key] = $value;
            } else {
                unset($data[$key]);
            }

            File::put($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return true;
    }
}
