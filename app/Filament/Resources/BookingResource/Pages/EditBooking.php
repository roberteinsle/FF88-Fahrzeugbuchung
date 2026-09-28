<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Services\BookingService;
use App\Services\DecisionService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Löschen')
                // Cancel first so pending requests are withdrawn and freed slots go back to earlier losers
                ->before(fn () => app(BookingService::class)->cancel($this->record)),
        ];
    }

    protected function afterSave(): void
    {
        app(DecisionService::class)->releaseSlotsFreedBy($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
