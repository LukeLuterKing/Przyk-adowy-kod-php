<?php

namespace App\Http\Controllers;

use App\Models\ArchiveOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class PDFController extends Controller
{
    public function generateReport()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        $orders = ArchiveOrder::whereBetween('created_at', [$startDate, $endDate])->get();

        $sortedOrders = $orders->sortBy('delivery_status');
        $totalRevenue = $orders->where('delivery_status', 'Wysłano')->sum('total_price');

        $pdf = Pdf::loadView('admin.raports.monthly', compact('sortedOrders', 'totalRevenue'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('monthly_report.pdf');
        
        // Alternatywnie, aby pobrać PDF
        // return $pdf->download('monthly_report.pdf');
    }
}
