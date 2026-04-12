<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSellerReportStatusRequest;
use App\Models\SellerReport;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class SellerReportController extends Controller
{
    public function index()
    {
        return view('backend.pages.seller-reports.index');
    }

    public function list()
    {
        if (request()->ajax()) {
            $data = SellerReport::query()
                ->with(['reporter:id,name,email', 'reportedUser:id,name,email'])
                ->latest();

            return DataTables::of($data)
                ->addColumn('reporter_name', function ($row) {
                    return e($row->reporter?->name ?? '—');
                })
                ->addColumn('reported_name', function ($row) {
                    return e($row->reportedUser?->name ?? '—');
                })
                ->addColumn('reason', function ($row) {
                    return e(Str::limit($row->reason, 80));
                })
                ->addColumn('status', function ($row) {
                    $map = [
                        'pending' => 'warning',
                        'reviewed' => 'info',
                        'resolved' => 'success',
                        'dismissed' => 'secondary',
                    ];
                    $cls = $map[$row->status] ?? 'secondary';

                    return '<span class="badge bg-'.$cls.'">'.e(ucfirst($row->status)).'</span>';
                })
                ->addColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y H:i');
                })
                ->addColumn('action', function ($row) {
                    $view = '<button data-url="'.route('admin.seller-reports.show', $row->id).'" data-modal-parent="#crudModal" class="btn btn-sm btn-info open_modal_btn"><i class="fas fa-eye"></i></button>';

                    return $view;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return abort(404);
    }

    public function show(string $id)
    {
        $report = SellerReport::with(['reporter:id,name,email', 'reportedUser:id,name,email'])->find($id);
        if (! $report) {
            return abort(404);
        }

        $html = view('backend.pages.seller-reports.show', compact('report'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function updateStatus(UpdateSellerReportStatusRequest $request, string $id)
    {
        $report = SellerReport::find($id);
        if (! $report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found',
            ], 404);
        }

        $report->update([
            'status' => $request->validated('status'),
            'admin_notes' => $request->validated('admin_notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report updated successfully',
        ]);
    }
}
