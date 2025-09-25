<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsDownloadController extends Controller
{
    public function appointmentsCsv(Request $r, ReportsService $rep): StreamedResponse
    {
        $start = $r->query('start', now()->startOfMonth()->toDateString());
        $end = $r->query('end', now()->toDateString());

        $filters = [
            'status' => $r->query('status'),
            'professional_id' => $r->query('professional_id'),
            'patient_id' => $r->query('patient_id'),
            'treatment_id' => $r->query('treatment_id'),
            'insurance_id' => $r->query('insurance_id'),
            'location_id' => $r->query('location_id'),
            'room_id' => $r->query('room_id'),
            'financial_status' => $r->query('financial_status'),
        ];

        $rows = $rep->datasetAppointments($start, $end, $filters);
        $headers = [
            'appointment_id', 'date', 'start_time', 'end_time', 'status', 'financial_status',
            'patient', 'professional', 'treatment', 'insurance', 'insurance_code', 'location', 'room',
            'price_final', 'paid_total', 'payout_snapshot',
        ];

        $filename = "appointments_{$start}_{$end}.csv";

        return response()->streamDownload(function () use ($rows, $headers) {
            $out = fopen('php://output', 'w');
            // separador ;
            fputcsv($headers, ';');
            foreach ($rows as $r) {
                $line = [];
                foreach ($headers as $h) {
                    $line[] = $r[$h] ?? null;
                }
                fputcsv($line, ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function paymentsCsv(Request $r, ReportsService $rep): StreamedResponse
    {
        $start = $r->query('start', now()->startOfMonth()->toDateString());
        $end = $r->query('end', now()->toDateString());

        $filters = [
            'status' => $r->query('status'),
            'method' => $r->query('method'),
            'professional_id' => $r->query('professional_id'),
            'insurance_id' => $r->query('insurance_id'),
            'location_id' => $r->query('location_id'),
            'room_id' => $r->query('room_id'),
            'min_amount' => $r->query('min_amount'),
        ];

        $rows = $rep->datasetPayments($start, $end, $filters);

        // normaliza received_at p/ string humana (igual fizemos no Livewire)
        foreach ($rows as &$r2) {
            $r2['received_at'] = ! empty($r2['received_at'])
                ? \Illuminate\Support\Carbon::parse($r2['received_at'])->format('d/m/Y H:i')
                : '—';
        }

        $headers = [
            'payment_id', 'payment_status', 'method', 'amount', 'payment_discount', 'amount_paid',
            'applied_to_due', 'surcharge_amount', 'received_at', 'reference',
            'appointment_id', 'appointment_date', 'start_time', 'end_time', 'appointment_status', 'financial_status',
            'price_final', 'appt_discount', 'paid_total',
            'patient', 'professional', 'treatment', 'insurance', 'insurance_code', 'location', 'room',
        ];

        $filename = "payments_{$start}_{$end}.csv";

        return response()->streamDownload(function () use ($rows, $headers) {
            $out = fopen('php://output', 'w');
            fputcsv($headers, ';');
            foreach ($rows as $r) {
                $line = [];
                foreach ($headers as $h) {
                    $line[] = $r[$h] ?? null;
                }
                fputcsv($line, ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
