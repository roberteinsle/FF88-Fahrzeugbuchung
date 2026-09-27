<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Löschen'),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record->fresh();

        // Protect the last admin: if no admin remains, revert
        if (! $record->is_admin) {
            $adminCount = User::where('is_admin', true)->count();
            if ($adminCount === 0) {
                $this->record->update(['is_admin' => true]);

                Notification::make()
                    ->title('Letzter Administrator')
                    ->body('Der letzte Administrator kann sich das Admin-Recht nicht selbst entziehen.')
                    ->danger()
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
