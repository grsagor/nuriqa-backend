<?php

namespace Modules\Gazian\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Gazian\Models\TradeEnquiry;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

class TradeEnquiryController extends Controller
{
    public function index()
    {
        return view('gazian::pages.trade-enquiries.index');
    }

    public function list()
    {
        if (! request()->ajax()) {
            return abort(404);
        }

        $data = TradeEnquiry::query()
            ->select('id', 'name', 'email', 'interest', 'business_type', 'country', 'city', 'is_read', 'created_at')
            ->latest();

        return DataTables::of($data)
            ->addColumn('is_read', function ($row) {
                $badgeClass = $row->is_read ? 'bg-success' : 'bg-warning';
                $text = $row->is_read ? 'Read' : 'Unread';

                return '<span class="badge '.$badgeClass.'">'.$text.'</span>';
            })
            ->addColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('d M Y H:i');
            })
            ->addColumn('action', function ($row) {
                $view = '<button data-url="'.route('gazian.admin.trade-enquiries.show', $row->id).'" data-modal-parent="#crudModal" class="btn btn-sm btn-info open_modal_btn"><i class="fas fa-eye"></i></button>';
                $delete = '<button data-url="'.route('gazian.admin.trade-enquiries.delete', $row->id).'" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                return $view.' '.$delete;
            })
            ->rawColumns(['is_read', 'action'])
            ->make(true);
    }

    public function show(int $id)
    {
        $enquiry = TradeEnquiry::query()->find($id);
        if ($enquiry === null) {
            return abort(404);
        }

        if (! $enquiry->is_read) {
            $enquiry->update(['is_read' => true]);
        }

        $html = view('gazian::pages.trade-enquiries.show', compact('enquiry'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function delete(int $id)
    {
        $enquiry = TradeEnquiry::query()->find($id);
        if ($enquiry === null) {
            return response()->json([
                'success' => false,
                'message' => 'Enquiry not found',
            ], 404);
        }

        $enquiry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trade enquiry deleted successfully',
        ]);
    }
}
