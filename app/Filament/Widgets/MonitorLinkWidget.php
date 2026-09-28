<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class MonitorLinkWidget extends Widget
{
    protected static string $view = 'filament.widgets.monitor-link';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $token = config('monitor.token');

        return [
            'adminUrl' => route('monitor'),
            'stationUrl' => $token ? route('monitor', ['key' => $token]) : null,
        ];
    }
}
