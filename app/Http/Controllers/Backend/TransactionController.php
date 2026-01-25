<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

class TransactionController extends Controller
{
    public function index()
    {
        return view('backend.pages.transactions.index');
    }

    public function list()
    {
        if (request()->ajax()) {
            $data = Transaction::with(['user', 'sellLines.product'])
                ->select('id', 'user_id', 'invoice_no', 'status', 'total', 'payment_method', 'created_at')
                ->latest();

            return DataTables::of($data)
                ->addColumn('user', function ($row) {
                    return $row->user ? $row->user->name : 'N/A';
                })
                ->addColumn('invoice_no', function ($row) {
                    return '<div class="fw-bold">'.$row->invoice_no.'</div>';
                })
                ->addColumn('total', function ($row) {
                    return '£'.number_format($row->total, 2);
                })
                ->addColumn('status', function ($row) {
                    $badgeClass = match ($row->status) {
                        'pending' => 'bg-warning',
                        'processing' => 'bg-info',
                        'completed' => 'bg-success',
                        'cancelled' => 'bg-danger',
                        default => 'bg-secondary'
                    };

                    return '<span class="badge '.$badgeClass.'">'.ucfirst($row->status).'</span>';
                })
                ->addColumn('payment_method', function ($row) {
                    return $row->payment_method ? ucfirst($row->payment_method) : 'N/A';
                })
                ->addColumn('items_count', function ($row) {
                    return $row->sellLines ? $row->sellLines->count() : 0;
                })
                ->addColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y H:i');
                })
                ->addColumn('action', function ($row) {
                    $view = '<button data-url="'.route('admin.transactions.show', $row->id).'" data-modal-parent="#crudModal" class="btn btn-sm btn-info open_modal_btn"><i class="fas fa-eye"></i></button>';
                    $delete = '<button data-url="'.route('admin.transactions.delete', $row->id).'" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                    return $view.' '.$delete;
                })
                ->rawColumns(['invoice_no', 'status', 'action'])
                ->make(true);
        }

        return abort(404);
    }

    public function show($id)
    {
        $transaction = Transaction::with(['user', 'sellLines.product', 'payments'])->find($id);
        if (! $transaction) {
            return abort(404);
        }

        $html = view('backend.pages.transactions.show', compact('transaction'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function delete($id)
    {
        $transaction = Transaction::find($id);
        if (! $transaction) {
            return abort(404);
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully',
        ]);
    }
}
