<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Services\BookingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Buchungen';
    protected static ?string $modelLabel = 'Buchung';
    protected static ?string $pluralModelLabel = 'Buchungen';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('vehicle_id')
                ->label('Fahrzeug')
                ->relationship('vehicle', 'name', fn ($query) => $query->where('is_active', true))
                ->required(),

            Forms\Components\Select::make('user_id')
                ->label('Nutzer')
                ->relationship('user', 'name')
                ->searchable(['name', 'email'])
                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->email})")
                ->required(),

            Forms\Components\Select::make('group_id')
                ->label('Für Gruppe')
                ->relationship('group', 'name')
                ->nullable(),

            Forms\Components\DateTimePicker::make('starts_at')
                ->label('Von')
                ->required()
                ->timezone('Europe/Berlin')
                ->displayFormat('d.m.Y H:i')
                ->seconds(false),

            Forms\Components\DateTimePicker::make('ends_at')
                ->label('Bis')
                ->required()
                ->timezone('Europe/Berlin')
                ->displayFormat('d.m.Y H:i')
                ->seconds(false)
                ->after('starts_at'),

            Forms\Components\TextInput::make('purpose')
                ->label('Zweck')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('destination')
                ->label('Ziel')
                ->maxLength(255),

            Forms\Components\Textarea::make('notes')
                ->label('Notiz')
                ->rows(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehicle.name')
                    ->label('Fahrzeug')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nutzer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Von')
                    ->dateTime('d.m.Y H:i')
                    ->timezone('Europe/Berlin')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Bis')
                    ->dateTime('d.m.Y H:i')
                    ->timezone('Europe/Berlin'),

                Tables\Columns\TextColumn::make('purpose')
                    ->label('Zweck')
                    ->limit(40),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        Booking::STATUS_PENDING => 'Wartet auf Entscheidung',
                        Booking::STATUS_REJECTED => 'Abgelehnt',
                        default => 'Bestätigt',
                    })
                    ->color(fn (string $state) => match ($state) {
                        Booking::STATUS_PENDING => 'warning',
                        Booking::STATUS_REJECTED => 'gray',
                        default => 'success',
                    }),

                Tables\Columns\IconColumn::make('cancelled_at')
                    ->label('Storniert')
                    ->boolean()
                    ->getStateUsing(fn (Booking $record) => $record->isCancelled()),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('vehicle_id')
                    ->label('Fahrzeug')
                    ->relationship('vehicle', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        Booking::STATUS_CONFIRMED => 'Bestätigt',
                        Booking::STATUS_PENDING => 'Wartet auf Entscheidung',
                        Booking::STATUS_REJECTED => 'Abgelehnt',
                    ]),

                Tables\Filters\TernaryFilter::make('cancelled')
                    ->label('Storniert')
                    ->nullable()
                    ->trueLabel('Nur stornierte')
                    ->falseLabel('Nur aktive')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('cancelled_at'),
                        false: fn ($query) => $query->whereNull('cancelled_at'),
                        blank: fn ($query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('cancel')
                    ->label('Stornieren')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->modalHeading('Buchung stornieren?')
                    ->visible(fn (Booking $record) => ! $record->isCancelled())
                    // Via the service so a pending request also closes its decision
                    ->action(fn (Booking $record) => app(BookingService::class)->cancel($record)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
