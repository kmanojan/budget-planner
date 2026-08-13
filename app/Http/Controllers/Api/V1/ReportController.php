<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ReportService $service) {}

    /**
     * API-RPT-01: Generate report data.
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
        ]);

        $data = $this->service->generateReport(
            $request->user(),
            $request->date_from,
            $request->date_to,
            $request->account_id ? (int) $request->account_id : null
        );

        return $this->success($data);
    }

    /**
     * API-RPT-02: Export as CSV download.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
        ]);

        $csv = $this->service->exportCsv(
            $request->user(),
            $request->date_from,
            $request->date_to,
            $request->account_id ? (int) $request->account_id : null
        );

        $filename = 'transactions_' . $request->date_from . '_to_' . $request->date_to . '.csv';

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
