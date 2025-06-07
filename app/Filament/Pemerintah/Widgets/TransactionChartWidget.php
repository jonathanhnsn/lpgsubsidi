<?php

namespace App\Filament\Widgets;

use App\Models\Transaksi;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TransactionChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Trend Transaksi (7 Hari Terakhir)';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = Transaksi::withoutUserRestriction()
            ->whereBetween('created_at', [now()->subDays(6), now()])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Generate labels untuk 7 hari terakhir
        $labels = [];
        $values = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('d M');
            
            $found = $data->firstWhere('date', $date);
            $values[] = $found ? $found->total : 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Transaksi',
                    'data' => $values,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}