<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVehicle extends EditRecord
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Löschen')
                ->before(function (Actions\DeleteAction $action) {
                    if ($this->record->bookings()->exists()) {
                        Notification::make()
                            ->title('Fahrzeug kann nicht gelöscht werden')
                            ->body('Es existieren Buchungen für dieses Fahrzeug. Deaktiviere es stattdessen.')
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
