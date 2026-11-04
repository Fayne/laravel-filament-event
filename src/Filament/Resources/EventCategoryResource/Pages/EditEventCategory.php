<?php

namespace Fayne\Event\Filament\Resources\EventCategoryResource\Pages;

use Fayne\Event\Filament\Resources\EventCategoryResource;
use Fayne\CMS\Filament\Operations\EditRecord;
use Fayne\CMS\Support\ContentLocale;
use Fayne\Event\Services\EventTranslationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;

class EditEventCategory extends EditRecord
{
    protected static string $resource = EventCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('translate')
                ->label(__('filament-event::multilingual.edit_translation'))
                ->visible(fn () => \Fayne\CMS\Support\ContentLocale::enabled() && $this->record->locale === ContentLocale::default() && (blank($this->record->fallback_id) || (string) $this->record->fallback_id === (string) $this->record->id))
                ->schema([
                    Select::make('locale')->label(__('filament-event::multilingual.locale'))
                        ->options(fn () => array_diff_key(ContentLocale::supported(), [ContentLocale::default() => true]))
                        ->required()->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            $text = $this->record->localizedTexts()->where('locale', $state)->first();
                            foreach (['title', 'subtitle', 'description', 'is_active'] as $field) {
                                $set($field, $text?->$field ?? ($field === 'is_active' ? false : null));
                            }
                        }),
                    TextInput::make('title')->label(__('filament-event::multilingual.title'))->required()->maxLength(255),
                    TextInput::make('subtitle')->label(__('filament-event::multilingual.subtitle'))->maxLength(255),
                    Textarea::make('description')->label(__('filament-event::multilingual.description')),
                    Toggle::make('is_active')->label(__('filament-event::multilingual.is_active'))->default(false),
                ])
                ->action(fn (array $data) => app(EventTranslationService::class)->save($this->record, $data['locale'], $data)),
        ];
    }
}
