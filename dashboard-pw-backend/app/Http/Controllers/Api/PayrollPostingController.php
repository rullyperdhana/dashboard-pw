<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollPosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollPostingController extends Controller
{
    /**
     * Get posting status for a year.
     */
    public function index(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $postings = PayrollPosting::where('year', $year)
            ->with('user:id,name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $postings,
            'meta' => [
                'year' => (int) $year
            ]
        ]);
    }

    /**
     * Post/Lock a period.
     */
    public function post(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'type' => 'required|string|in:PNS,PPPK,PPPK_PW,TPG,TPP',
        ]);

        $posting = PayrollPosting::updateOrCreate(
            [
                'year' => $request->year,
                'month' => $request->month,
                'type' => $request->type,
            ],
            [
                'is_posted' => true,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Data {$request->type} periode {$request->month}/{$request->year} berhasil di-POSTING (Dikunci).",
            'data' => $posting->load('user:id,name'),
        ]);
    }

    /**
     * Unpost/Unlock a period.
     */
    public function unpost(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'type' => 'required|string|in:PNS,PPPK,PPPK_PW,TPG,TPP',
        ]);

        $posting = PayrollPosting::where('year', $request->year)
            ->where('month', $request->month)
            ->where('type', $request->type)
            ->first();

        if ($posting) {
            $posting->update([
                'is_posted' => false,
                'posted_at' => null,
                'posted_by' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Data {$request->type} periode {$request->month}/{$request->year} berhasil di-UNPOSTING (Dibuka Kunci).",
            'data' => $posting,
        ]);
    }
}
