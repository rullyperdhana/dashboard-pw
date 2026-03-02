<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\Employee;
use App\Models\PayrollPosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    /**
     * List pembayaran
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Payment::query();

        if ($user->isAdminSkpd()) {
            // Only show payments that have details for this SKPD
            $query->whereHas('details.employee', function ($q) use ($user) {
                $q->where('idskpd', $user->institution);
            });

            // We need to load details filtered by SKPD to recalculate totals
            $query->with([
                'details' => function ($q) use ($user) {
                    $q->whereHas('employee', function ($eq) use ($user) {
                        $eq->where('idskpd', $user->institution);
                    });
                }
            ]);
        } else {
            $query->with('details');
        }

        // Filter berdasarkan tahun
        if ($request->has('year')) {
            $query->byYear($request->year);
        }

        // Filter berdasarkan bulan
        if ($request->has('month')) {
            $query->byMonth($request->month);
        }

        $payments = $query->latest()->paginate($request->per_page ?? 15);

        // Transform if SKPD Admin to show SKPD-specific totals
        if ($user->isAdminSkpd()) {
            $payments->getCollection()->transform(function ($payment) {
                $payment->total_amoun = $payment->details->sum('total_amoun');
                $payment->total_emplo = $payment->details->count();
                return $payment;
            });
        } else {
            // For Global Admin, we might still want to count beneficiaries if not in table
            $payments->getCollection()->transform(function ($payment) {
                if (!$payment->total_emplo) {
                    $payment->total_emplo = $payment->details->count();
                }
                return $payment;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    /**
     * Detail pembayaran
     */
    public function show($id, Request $request)
    {
        $user = $request->user();
        $skpdId = $request->skpd_id; // Optional filter from query param
        $query = Payment::with(['rkaSetting']);

        if ($user->isAdminSkpd()) {
            $query->with([
                'details' => function ($q) use ($user) {
                    $q->whereHas('employee', function ($eq) use ($user) {
                        $eq->where('idskpd', $user->institution);
                    });
                },
                'details.employee.skpd'
            ]);
        } elseif ($skpdId) {
            // Filter by specific SKPD when coming from reports page
            $query->with([
                'details' => function ($q) use ($skpdId) {
                    $q->whereHas('employee', function ($eq) use ($skpdId) {
                        $eq->where('idskpd', $skpdId);
                    });
                },
                'details.employee.skpd'
            ]);
        } else {
            $query->with(['details.employee.skpd']);
        }

        $payment = $query->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    /**
     * Buat pembayaran baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_month' => 'required|integer|min:1|max:12',
            'payment_year' => 'required|integer',
            'rka_id' => 'nullable|exists:rka_settings,id',
            'employee_payments' => 'required|array',
            'employee_payments.*.employee_id' => 'required|exists:pegawai_pw,id',
            'employee_payments.*.gaji_pokok' => 'required|numeric|min:0',
            'employee_payments.*.pajak' => 'nullable|numeric|min:0',
            'employee_payments.*.iwp' => 'nullable|numeric|min:0',
            'employee_payments.*.tunjangan' => 'nullable|numeric|min:0',
            'employee_payments.*.potongan' => 'nullable|numeric|min:0',
        ]);

        // Check if posted
        if (PayrollPosting::isLocked((int) $validated['payment_year'], (int) $validated['payment_month'], 'PPPK_PW')) {
            return response()->json([
                'success' => false,
                'message' => "Data PPPK Paruh Waktu periode {$validated['payment_month']}/{$validated['payment_year']} sudah di-POSTING (Dikunci) dan tidak dapat diubah."
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Hitung total
            $totalAmount = 0;
            foreach ($validated['employee_payments'] as $emp) {
                $empTotal = ($emp['gaji_pokok'] ?? 0) - ($emp['pajak'] ?? 0) - ($emp['iwp'] ?? 0) + ($emp['tunjangan'] ?? 0) - ($emp['potongan'] ?? 0);
                $totalAmount += $empTotal;
            }

            // Buat payment header
            $payment = Payment::create([
                'rka_id' => $validated['rka_id'] ?? null,
                'month' => $validated['payment_month'],
                'year' => $validated['payment_year'],
                'total_amoun' => $totalAmount,
                'status' => 'paid', // Status default from table logic
            ]);

            // Buat payment details
            foreach ($validated['employee_payments'] as $empPayment) {
                PaymentDetail::create([
                    'payment_id' => $payment->id,
                    'employee_id' => $empPayment['employee_id'],
                    'gaji_pokok' => $empPayment['gaji_pokok'],
                    'pajak' => $empPayment['pajak'] ?? 0,
                    'iwp' => $empPayment['iwp'] ?? 0,
                    'tunjangan' => $empPayment['tunjangan'] ?? 0,
                    'potongan' => $empPayment['potongan'] ?? 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dibuat',
                'data' => $payment->load('details'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve pembayaran
     */
    public function approve($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->update(['status' => Payment::STATUS_APPROVED]);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil disetujui',
            'data' => $payment,
        ]);
    }

    /**
     * Download PDF
     */
    public function downloadPdf($id, Request $request)
    {
        $user = $request->user();
        $query = Payment::query();

        if ($user->isAdminSkpd()) {
            $query->with([
                'details' => function ($q) use ($user) {
                    $q->whereHas('employee', function ($eq) use ($user) {
                        $eq->where('idskpd', $user->institution);
                    });
                },
                'details.employee'
            ]);
        } else {
            $query->with(['details.employee']);
        }

        $payment = $query->findOrFail($id);

        $pdf = Pdf::loadView('reports.payroll', compact('payment'));

        return $pdf->download("payroll-{$payment->month}-{$payment->year}.pdf");
    }
}
