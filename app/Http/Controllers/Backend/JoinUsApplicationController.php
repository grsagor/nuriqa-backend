<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\JoinUsApplication;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

class JoinUsApplicationController extends Controller
{
    public function index()
    {
        return view('backend.pages.join-us-applications.index');
    }

    public function list()
    {
        if (request()->ajax()) {
            $data = JoinUsApplication::select('id', 'type', 'full_name', 'email', 'phone', 'status', 'created_at')
                ->latest();

            return DataTables::of($data)
                ->addColumn('type', function ($row) {
                    $badgeClass = $row->type === 'model' ? 'bg-primary' : 'bg-info';

                    return '<span class="badge '.$badgeClass.'">'.ucfirst($row->type).'</span>';
                })
                ->addColumn('status', function ($row) {
                    $badgeClass = match ($row->status) {
                        'pending' => 'bg-warning',
                        'reviewed' => 'bg-info',
                        'accepted' => 'bg-success',
                        'rejected' => 'bg-danger',
                        default => 'bg-secondary'
                    };

                    return '<span class="badge '.$badgeClass.'">'.ucfirst($row->status ?? 'pending').'</span>';
                })
                ->addColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y');
                })
                ->addColumn('action', function ($row) {
                    $view = '<button data-url="'.route('admin.join-us-applications.show', $row->id).'" data-modal-parent="#crudModal" class="btn btn-sm btn-info open_modal_btn"><i class="fas fa-eye"></i></button>';
                    $approve = '<button data-url="'.route('admin.join-us-applications.approve', $row->id).'" class="btn btn-sm btn-success crud_action_btn" data-action="approve"><i class="fas fa-check"></i></button>';
                    $reject = '<button data-url="'.route('admin.join-us-applications.reject', $row->id).'" class="btn btn-sm btn-danger crud_action_btn" data-action="reject"><i class="fas fa-times"></i></button>';
                    $delete = '<button data-url="'.route('admin.join-us-applications.delete', $row->id).'" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                    $statusActions = (in_array($row->status, ['pending', 'reviewed']) || ! $row->status) ? $approve.' '.$reject : '';

                    return $view.' '.$statusActions.' '.$delete;
                })
                ->rawColumns(['type', 'status', 'action'])
                ->make(true);
        }

        return abort(404);
    }

    public function show($id)
    {
        $application = JoinUsApplication::find($id);
        if (! $application) {
            return abort(404);
        }

        $html = view('backend.pages.join-us-applications.show', compact('application'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function approve($id)
    {
        $application = JoinUsApplication::find($id);
        if (! $application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found',
            ], 404);
        }

        $application->update(['status' => 'accepted']);

        return response()->json([
            'success' => true,
            'message' => 'Application accepted successfully',
        ]);
    }

    public function reject($id)
    {
        $application = JoinUsApplication::find($id);
        if (! $application) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found',
            ], 404);
        }

        $application->update(['status' => 'rejected']);

        return response()->json([
            'success' => true,
            'message' => 'Application rejected successfully',
        ]);
    }

    public function delete($id)
    {
        $application = JoinUsApplication::find($id);
        if (! $application) {
            return abort(404);
        }

        $application->delete();

        return response()->json([
            'success' => true,
            'message' => 'Application deleted successfully',
        ]);
    }
}
