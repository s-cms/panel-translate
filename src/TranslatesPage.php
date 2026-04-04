<?php

namespace SmartCms\PanelTranslate;

use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Form as ComponentsForm;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SmartCms\PanelTranslate\Actions\ScanTranslates;
use SmartCms\PanelTranslate\Actions\UpdateTranslate;
use UnitEnum;

class TranslatesPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'panel-translate::filament.pages.translates';

    protected static ?int $navigationSort = 99;

    public static function getNavigationLabel(): string
    {
        return str(__('panel-translate::admin.translates'))
            ->kebab()
            ->replace('-', ' ')
            ->ucwords();
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return PanelTranslatePlugin::$navigationGroup ? __(PanelTranslatePlugin::$navigationGroup) : null;
    }

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedLanguage;

    public ?array $data = [];

    public function form(ComponentsForm $form): ComponentsForm
    {
        return $form
            ->schema([
                TextInput::make('key')
                    ->label(__('panel-translate::admin.key'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn() => $this->resetTable()),
                Textarea::make('value')
                    ->label(__('panel-translate::admin.value'))
                    ->required()
                    ->rows(3),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordAction('edit')
            ->records(
                fn(?string $search, ?string $sortColumn, ?string $sortDirection): Collection => ScanTranslates::run()
                    ->when(
                        filled($search),
                        fn(Collection $data): Collection => $data->filter(
                            fn(array $record): bool => str_contains(
                                Str::lower($record['key']),
                                Str::lower($search),
                            ),
                        )
                    )
                    ->when(
                        filled($sortColumn),
                        function (Collection $data) use ($sortColumn, $sortDirection) {
                            return $data->sortBy(
                                $sortColumn,
                                SORT_REGULAR,
                                $sortDirection === 'desc' ? SORT_DESC : SORT_ASC,
                            );
                        }
                    )
            )
            ->columns([
                TextColumn::make('key')
                    ->label(__('panel-translate::admin.key'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('count')
                    ->label(__('panel-translate::admin.count'))
                    ->formatStateUsing(fn(int $state): string => $state . '/' . $this->getCountAvailableLanguages())
                    ->badge()
                    ->color(fn(int $state): string => $state === $this->getCountAvailableLanguages() ? 'success' : 'warning')
                    ->wrap(),
                TextColumn::make('values')
                    ->label(__('panel-translate::admin.values'))
                    ->getStateUsing(function ($record) {
                        $values = implode(',', ($record['values'] ?? []));
                        if (strlen(str_replace(',', '', $values)) == 0) {
                            return ' - ';
                        }
                        return $values;
                    })
                    ->limit(50)
                    ->wrap(),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('edit')
                    ->label(__('filament-actions::edit.single.label'))
                    ->recordTitle(fn(array $record): string => $record['key'])
                    ->icon(FilamentIcon::resolve('actions::edit-action') ?? Heroicon::PencilSquare)
                    ->schema(function (): array {
                        $languages = $this->getAvailableLanguages();
                        $form = [
                            TextInput::make('key')
                                ->label(__('panel-translate::admin.key'))
                                ->readOnly(),
                        ];
                        foreach ($languages as $language => $languageName) {
                            $form[] = TextInput::make("values.{$language}")
                                ->label($languageName)
                                ->required();
                        }

                        return $form;
                    })
                    ->fillForm(fn(array $record): array => [
                        'key' => $record['key'],
                        'values' => $this->getFormValues($record),
                    ])
                    ->action(function (array $data): void {
                        UpdateTranslate::run($data);
                        Notification::make()
                            ->title(__('filament-actions::edit.single.notifications.saved.title'))
                            ->success()
                            ->send();
                        $this->resetTable();
                    }),
            ])
            ->searchable()
            ->defaultSort('key', 'asc')
            ->filters([]);
    }

    protected function getAvailableLanguages(): array
    {
        $languages = [];
        $langPath = lang_path();

        if (File::exists($langPath)) {
            // Parse directories
            foreach (File::directories($langPath) as $dir) {
                $locale = basename($dir);
                $languages[$locale] = $this->getLanguageName($locale);
            }

            // Parse JSON files
            foreach (File::files($langPath) as $file) {
                if ($file->getExtension() === 'json') {
                    $locale = $file->getFilenameWithoutExtension();
                    $languages[$locale] = $this->getLanguageName($locale);
                }
            }
        }

        if (empty($languages)) {
            $languages['en'] = $this->getLanguageName('en');
        }

        return $languages;
    }

    protected function getCountAvailableLanguages(): int
    {
        return once(fn() => count($this->getAvailableLanguages()));
    }

    protected function getLanguageName(string $locale): string
    {
        $names = [
            'en' => 'English',
            'ru' => 'Русский',
            'es' => 'Español',
            'fr' => 'Français',
            'de' => 'Deutsch',
            'it' => 'Italiano',
            'pt' => 'Português',
            'ja' => '日本語',
            'ko' => '한국어',
            'zh' => '中文',
            'uk' => 'Українська',
        ];

        return $names[$locale] ?? $locale;
    }

    protected function getFormValues(array $record): array
    {
        $values = $record['values'] ?? [];
        $currentLocale = app()->getLocale();
        
        // Check if all translations are empty
        $allEmpty = empty(array_filter($values, fn($value) => !empty(trim($value))));
        
        // If all translations are empty, prefill current locale with the key
        if ($allEmpty && isset($this->getAvailableLanguages()[$currentLocale])) {
            $values[$currentLocale] = $record['key'];
        }
        
        return $values;
    }

    public function isTableColumnToggledHidden(string $name): bool
    {
        return false;
    }

    public function getSelectedTableRecordsQuery(bool $shouldFetchSelectedRecords = true, ?int $chunkSize = null): Builder
    {
        return \Illuminate\Database\Eloquent\Model::query();
    }
}
