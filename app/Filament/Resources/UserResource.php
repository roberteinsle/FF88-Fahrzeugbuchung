<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Group;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use App\Services\MagicLinkService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Nutzer';
    protected static ?string $modelLabel = 'Nutzer';
    protected static ?string $pluralModelLabel = 'Nutzer';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->label('E-Mail-Adresse')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Forms\Components\TextInput::make('phone')
                ->label('Telefonnummer')
                ->tel()
                ->maxLength(30)
                ->helperText('Optional – wird im Konfliktfall für andere Nutzer angezeigt'),

            Forms\Components\CheckboxList::make('groups')
                ->label('Gruppen')
                ->relationship('groups', 'name')
                ->columns(2),

            Forms\Components\Toggle::make('is_admin')
                ->label('Administrator')
                ->helperText('Admins haben Zugriff auf das Admin-Backend und können alle Buchungen bearbeiten'),

            Forms\Components\Toggle::make('is_decider')
                ->label('Entscheider')
                ->helperText('Entscheider werden bei Buchungskonflikten per E-Mail gefragt und können entscheiden, wer das Fahrzeug bekommt'),

            Forms\Components\Toggle::make('is_active')
                ->label('Aktiv')
                ->default(true)
                ->helperText('Deaktivierte Nutzer können sich nicht anmelden'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable(),

                Tables\Columns\TextColumn::make('groups.name')
                    ->label('Gruppen')
                    ->badge()
                    ->separator(', '),

                Tables\Columns\IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_decider')
                    ->label('Entscheider')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Letzter Login')
                    ->dateTime('d.m.Y H:i')
                    ->timezone('Europe/Berlin')
                    ->placeholder('Noch nie'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('groups')
                    ->label('Gruppe')
                    ->relationship('groups', 'name', fn ($query) => $query->orderBy('sort_order'))
                    ->multiple()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_admin')->label('Administrator'),
                Tables\Filters\TernaryFilter::make('is_decider')->label('Entscheider'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktiv'),
            ])
            ->actions([
                Tables\Actions\Action::make('send_login_link')
                    ->label('Login-Link senden')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->action(function (User $record, MagicLinkService $service) {
                        if (! $record->is_active) {
                            Notification::make()
                                ->title('Nutzer ist deaktiviert')
                                ->danger()
                                ->send();
                            return;
                        }
                        try {
                            $service->sendLink($record->email, request());
                            Notification::make()
                                ->title('Login-Link gesendet')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Fehler beim Senden')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
