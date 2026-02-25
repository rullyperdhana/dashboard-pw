<?php

namespace App\Http\Controllers\Api;

use App\Exports\EmployeesExport;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    /**
     * List pegawai dengan filter dan pagination
     */
    public function index(Request $request)
    {
        $query = Employee::query()->with('skpd');

        // Filter berdasarkan SKPD (untuk admin_skpd atau request global)
        if ($request->user()->isAdminSkpd()) {
            $query->where('idskpd', $request->user()->institution);
        } elseif ($request->has('skpd_id')) {
            $query->where('idskpd', $request->skpd_id);
        }

        // Filter berdasarkan status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        // Clone query for stats before pagination
        $statsQuery = clone $query;
        $stats = [
            'total' => $statsQuery->count(),
            'male' => (clone $statsQuery)->where('jk', 'LAKI - LAKI')->count(),
            'female' => (clone $statsQuery)->where('jk', 'PEREMPUAN')->count(),
        ];

        $employees = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $employees,
            'stats' => $stats,
        ]);
    }

    /**
     * Export employees to Excel or PDF
     */
    public function export(Request $request)
    {
        $query = Employee::query()->with('skpd');

        // Apply same filters as index
        if ($request->user()->isAdminSkpd()) {
            $query->where('idskpd', $request->user()->institution);
        } elseif ($request->has('skpd_id')) {
            $query->where('idskpd', $request->skpd_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('nama')->get()->toArray();
        $format = $request->format ?? 'excel';
        $filename = 'data_pegawai_' . date('Ymd_His');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.employees-pdf', [
                'data' => $employees,
                'total' => count($employees),
            ]);

            $pdf->setPaper('a4', 'landscape');

            return $pdf->download($filename . '.pdf');
        }

        return Excel::download(new EmployeesExport($employees), $filename . '.xlsx');
    }

    /**
     * Detail pegawai
     */
    public function show($id)
    {
        $employee = Employee::with(['skpd', 'user', 'paymentDetails.payment'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $employee,
        ]);
    }

    /**
     * Tambah pegawai baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'idskpd' => 'required|exists:skpd,id_skpd',
            'nip' => 'required|string|unique:pegawai_pw,nip',
            'nik' => 'nullable|string',
            'nama' => 'required|string',
            'tempat_lahir' => 'nullable|string',
            'tgl_lahir' => 'nullable|date',
            'jk' => 'required|in:LAKI - LAKI,PEREMPUAN',
            'status' => 'nullable|string',
            'no_hp' => 'nullable|string',
            'agama' => 'nullable|string',
            'golru' => 'nullable|string',
            'tmt_golru' => 'nullable|date',
            'jabatan' => 'nullable|string',
            'eselon' => 'nullable|string',
            'jenis_jabatan' => 'nullable|string',
            'tmt_jabatan' => 'nullable|date',
            'upt' => 'nullable|string',
            'satker' => 'nullable|string',
            'mk_thn' => 'nullable|integer',
            'mk_bln' => 'nullable|integer',
            'tk_ijazah' => 'nullable|string',
            'nm_pendidikan' => 'nullable|string',
            'th_lulus' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'gapok' => 'nullable|numeric',
            'tunjangan' => 'nullable|numeric',
            'pajak' => 'nullable|numeric',
            'iwp' => 'nullable|numeric',
            'potongan' => 'nullable|numeric',
        ]);

        $employee = Employee::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pegawai berhasil ditambahkan',
            'data' => $employee->load('skpd'),
        ], 201);
    }

    /**
     * Update pegawai
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'idskpd' => 'sometimes|exists:skpd,id_skpd',
            'nip' => 'sometimes|string|unique:pegawai_pw,nip,' . $id,
            'nik' => 'nullable|string',
            'nama' => 'sometimes|string',
            'tempat_lahir' => 'nullable|string',
            'tgl_lahir' => 'nullable|date',
            'jk' => 'sometimes|in:LAKI - LAKI,PEREMPUAN',
            'status' => 'nullable|string',
            'no_hp' => 'nullable|string',
            'agama' => 'nullable|string',
            'golru' => 'nullable|string',
            'tmt_golru' => 'nullable|date',
            'jabatan' => 'nullable|string',
            'eselon' => 'nullable|string',
            'jenis_jabatan' => 'nullable|string',
            'tmt_jabatan' => 'nullable|date',
            'upt' => 'nullable|string',
            'satker' => 'nullable|string',
            'mk_thn' => 'nullable|integer',
            'mk_bln' => 'nullable|integer',
            'tk_ijazah' => 'nullable|string',
            'nm_pendidikan' => 'nullable|string',
            'th_lulus' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'gapok' => 'nullable|numeric',
            'tunjangan' => 'nullable|numeric',
            'pajak' => 'nullable|numeric',
            'iwp' => 'nullable|numeric',
            'potongan' => 'nullable|numeric',
        ]);

        $employee->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Data pegawai berhasil diperbarui',
            'data' => $employee->load('skpd'),
        ]);
    }

    /**
     * Hapus pegawai
     */
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pegawai berhasil dihapus',
        ]);
    }

    /**
     * Get payroll history for a specific employee
     */
    public function payrollHistory($id)
    {
        $employee = Employee::with('skpd')->findOrFail($id);

        $history = \App\Models\PaymentDetail::with('payment')
            ->where('employee_id', $id)
            ->get()
            ->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'month' => $detail->payment->month,
                    'year' => $detail->payment->year,
                    'gaji_pokok' => $detail->gaji_pokok,
                    'tunjangan' => $detail->tunjangan,
                    'potongan' => $detail->potongan,
                    'pajak' => $detail->pajak,
                    'iwp' => $detail->iwp,
                    'total_bersih' => $detail->total_amoun,
                    'notes' => $detail->notes,
                    'created_at' => $detail->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->sortByDesc(function ($item) {
                return $item['year'] * 100 + $item['month'];
            })
            ->values();

        return response()->json([
            'success' => true,
            'employee' => $employee,
            'data' => $history,
        ]);
    }

    /**
     * Export individual payroll history to PDF
     */
    public function exportIndividualPayroll($id)
    {
        $employee = Employee::with('skpd')->findOrFail($id);

        $history = \App\Models\PaymentDetail::with('payment')
            ->where('employee_id', $id)
            ->get()
            ->sortByDesc(function ($detail) {
                return $detail->payment->year * 100 + $detail->payment->month;
            });

        $pdf = Pdf::loadView('exports.individual-payroll-pdf', [
            'employee' => $employee,
            'history' => $history,
            'generated_at' => date('d/m/Y H:i:s'),
        ]);

        $pdf->setPaper('a4', 'portrait');

        $filename = 'payroll_trace_' . $employee->nip . '_' . date('YmdHis');
        return $pdf->download($filename . '.pdf');
    }
}

