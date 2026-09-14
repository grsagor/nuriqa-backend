<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\SponsorRequest;
use App\Services\SponsorRequestModerationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class SponsorRequestController extends Controller
{
    public function __construct(protected SponsorRequestModerationService $moderationService) {}

    public function index()
    {
        return view('backend.pages.sponsor-requests.index');
    }

    public function list()
    {
        if (request()->ajax()) {
            $data = SponsorRequest::with(['user', 'product'])
                ->select('id', 'user_id', 'product_id', 'first_name', 'last_name', 'email', 'phone', 'status', 'created_at')
                ->latest();

            return DataTables::of($data)
                ->addColumn('user', function ($row) {
                    return $row->user ? $row->user->name : 'N/A';
                })
                ->addColumn('product', function ($row) {
                    return $row->product ? $row->product->title : 'N/A';
                })
                ->addColumn('name', function ($row) {
                    return $row->first_name.' '.$row->last_name;
                })
                ->addColumn('status', function ($row) {
                    $badgeClass = match ($row->status) {
                        'pending' => 'bg-warning',
                        'approved' => 'bg-success',
                        'rejected' => 'bg-danger',
                        'returned' => 'bg-info',
                        default => 'bg-secondary'
                    };

                    return '<span class="badge '.$badgeClass.'">'.ucfirst($row->status).'</span>';
                })
                ->addColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y');
                })
                ->addColumn('action', function ($row) {
                    $view = '<button data-url="'.route('admin.sponsor-requests.show', $row->id).'" data-modal-parent="#crudModal" class="btn btn-sm btn-info open_modal_btn"><i class="fas fa-eye"></i></button>';

                    return $view;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        return abort(404);
    }

    public function show($id)
    {
        $sponsorRequest = SponsorRequest::with(['user', 'product'])->find($id);
        if (! $sponsorRequest) {
            return abort(404);
        }

        $html = view('backend.pages.sponsor-requests.show', compact('sponsorRequest'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $sponsorRequest = SponsorRequest::find($id);
        if (! $sponsorRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Sponsor request not found',
            ], 404);
        }

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->moderationService->approve($sponsorRequest, $request->user(), $data['message'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Sponsor request approved successfully',
        ]);
    }

    public function reject(Request $request, $id)
    {
        $sponsorRequest = SponsorRequest::find($id);
        if (! $sponsorRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Sponsor request not found',
            ], 404);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $this->moderationService->reject($sponsorRequest, $request->user(), $data['message'], $data['message']);

        return response()->json([
            'success' => true,
            'message' => 'Sponsor request rejected successfully',
        ]);
    }

    public function returnForCorrection(Request $request, $id)
    {
        $sponsorRequest = SponsorRequest::find($id);
        if (! $sponsorRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Sponsor request not found',
            ], 404);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $this->moderationService->returnForCorrection($sponsorRequest, $request->user(), $data['message']);

        return response()->json([
            'success' => true,
            'message' => 'Sponsor request returned for correction',
        ]);
    }

    public function delete($id)
    {
        $sponsorRequest = SponsorRequest::find($id);
        if (! $sponsorRequest) {
            return abort(404);
        }

        $sponsorRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sponsor request deleted successfully',
        ]);
    }
}
