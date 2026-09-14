<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\Wallet;
use App\Services\AuditLogService;
use App\Services\LedgerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            // All rows in `transactions` (line items may be missing on legacy data).
            $data = Transaction::with(['user', 'sellLines.product', 'latestPayment'])
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
                    return $this->formatPaymentSummary($row);
                })
                ->addColumn('items_count', function ($row) {
                    return $row->sellLines ? $row->sellLines->count() : 0;
                })
                ->addColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y H:i');
                })
                ->addColumn('action', function ($row) {
                    $view = '<button data-url="'.route('admin.transactions.show', $row->id).'" data-modal-parent="#crudModal" class="btn btn-sm btn-info open_modal_btn"><i class="fas fa-eye"></i></button>';
                    $complete = $row->status === 'pending' ? '<a href="'.route('admin.transactions.complete', $row->id).'" class="btn btn-sm btn-success" onclick="return confirm(\'Mark this order as completed and credit seller wallets?\')"><i class="fas fa-check"></i> Complete</a>' : '';
                    $delete = '<button data-url="'.route('admin.transactions.delete', $row->id).'" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                    return $view.' '.$complete.' '.$delete;
                })
                ->rawColumns(['invoice_no', 'status', 'action'])
                ->make(true);
        }

        return abort(404);
    }

    public function hajraIndex()
    {
        return view('backend.pages.transactions.hajra');
    }

    public function hajraList()
    {
        if (request()->ajax()) {
            $data = Transaction::with(['user', 'sellLines.product', 'latestPayment'])
                ->whereHas('sellLines.product', function ($query) {
                    $query->where('type', 'hajra');
                })
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
                    return $this->formatPaymentSummary($row);
                })
                ->addColumn('items_count', function ($row) {
                    return $row->sellLines ? $row->sellLines->count() : 0;
                })
                ->addColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y H:i');
                })
                ->addColumn('action', function ($row) {
                    $view = '<button data-url="'.route('admin.transactions.show', $row->id).'" data-modal-parent="#crudModal" class="btn btn-sm btn-info open_modal_btn"><i class="fas fa-eye"></i></button>';
                    $complete = $row->status === 'pending' ? '<a href="'.route('admin.transactions.complete', $row->id).'" class="btn btn-sm btn-success" onclick="return confirm(\'Mark this order as completed and credit seller wallets?\')"><i class="fas fa-check"></i> Complete</a>' : '';
                    $delete = '<button data-url="'.route('admin.transactions.delete', $row->id).'" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                    return $view.' '.$complete.' '.$delete;
                })
                ->rawColumns(['invoice_no', 'status', 'action'])
                ->make(true);
        }

        return abort(404);
    }

    public function merchandiseIndex()
    {
        return view('backend.pages.transactions.merchandise');
    }

    public function merchandiseList()
    {
        if (request()->ajax()) {
            $data = Transaction::with(['user', 'sellLines.product', 'latestPayment'])
                ->whereHas('sellLines.product', function ($query) {
                    $query->where('type', 'merchandise');
                })
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
                    return $this->formatPaymentSummary($row);
                })
                ->addColumn('items_count', function ($row) {
                    return $row->sellLines ? $row->sellLines->count() : 0;
                })
                ->addColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y H:i');
                })
                ->addColumn('action', function ($row) {
                    $view = '<button data-url="'.route('admin.transactions.show', $row->id).'" data-modal-parent="#crudModal" class="btn btn-sm btn-info open_modal_btn"><i class="fas fa-eye"></i></button>';
                    $complete = $row->status === 'pending' ? '<a href="'.route('admin.transactions.complete', $row->id).'" class="btn btn-sm btn-success" onclick="return confirm(\'Mark this order as completed and credit seller wallets?\')"><i class="fas fa-check"></i> Complete</a>' : '';
                    $delete = '<button data-url="'.route('admin.transactions.delete', $row->id).'" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                    return $view.' '.$complete.' '.$delete;
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

    public function complete($id)
    {
        $transaction = Transaction::with(['sellLines.product', 'latestPayment'])->find($id);
        if (! $transaction) {
            return abort(404);
        }

        if ($transaction->status === 'completed') {
            return redirect()->back()
                ->with('info', 'Transaction is already completed.');
        }

        $payment = $transaction->latestPayment;
        if ($payment && in_array($payment->payment_method, ['stripe', 'paypal'], true) && $payment->status !== 'succeeded') {
            return redirect()->back()
                ->with('error', 'Order cannot be marked as completed until the payment succeeds.');
        }

        DB::beginTransaction();
        try {
            $fromStatus = $transaction->status;
            $transaction->update(['status' => 'completed']);

            $sellerEarnings = [];
            foreach ($transaction->sellLines as $sellLine) {
                if ($sellLine->product && $sellLine->product->owner_id) {
                    $sellerId = $sellLine->product->owner_id;
                    $subtotal = (float) $sellLine->subtotal;
                    if ($sellLine->getRawOriginal('donation_amount') === null) {
                        $donationAmount = 0.0;
                        if ($sellLine->product->platform_donation && (int) $sellLine->product->donation_percentage > 0) {
                            $donationAmount = $subtotal * ((float) $sellLine->product->donation_percentage / 100);
                        }
                    } else {
                        $donationAmount = (float) $sellLine->donation_amount;
                    }
                    $earnings = $subtotal - $donationAmount;

                    if (! isset($sellerEarnings[$sellerId])) {
                        $sellerEarnings[$sellerId] = 0;
                    }
                    $sellerEarnings[$sellerId] += $earnings;
                }
            }

            foreach ($sellerEarnings as $sellerId => $amount) {
                $wallet = Wallet::getOrCreateForUser($sellerId);
                $wallet->total_earnings += $amount;
                $wallet->available_balance += $amount;
                $wallet->save();
            }

            DB::commit();

            app(AuditLogService::class)->record(
                'transaction.complete',
                $transaction,
                auth()->user(),
                $fromStatus,
                'completed',
            );

            return redirect()->back()
                ->with('success', 'Order marked as completed and seller wallets credited.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to complete order: '.$e->getMessage());
        }
    }

    public function refund(Request $request, $id)
    {
        $transaction = Transaction::query()->findOrFail($id);

        if (($transaction->refund_status ?? null) === 'refunded') {
            return response()->json([
                'success' => false,
                'message' => 'This order is already refunded.',
            ], 422);
        }

        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $amount = round((float) ($data['amount'] ?? $transaction->total), 2);
        if ($amount > (float) $transaction->total) {
            return response()->json([
                'success' => false,
                'message' => 'Refund exceeds order total.',
            ], 422);
        }

        $from = $transaction->status;
        $actor = $request->user();

        $transaction->update([
            'refund_status' => 'refunded',
            'refunded_amount' => $amount,
        ]);

        $payment = $transaction->payments()->latest()->first();
        if ($payment) {
            $payment->update(['status' => 'refunded']);
        }

        app(LedgerService::class)->recordRefund($transaction, $amount, $actor);
        app(AuditLogService::class)->record(
            'order.refund',
            $transaction,
            $actor,
            $from,
            $transaction->fresh()->status,
            $data['reason'] ?? null,
            ['amount' => $amount],
        );

        return response()->json([
            'success' => true,
            'message' => 'Refund recorded.',
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

    private function formatPaymentSummary(Transaction $transaction): string
    {
        $payment = $transaction->latestPayment;
        if (! $payment) {
            return $transaction->payment_method ? ucfirst($transaction->payment_method) : 'N/A';
        }

        return $this->paymentProviderLabel($payment).' ('.ucfirst($payment->status).')';
    }

    private function paymentProviderLabel(TransactionPayment $payment): string
    {
        return match ($payment->payment_method) {
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'bank' => 'Bank',
            'cod' => 'Cash-on-Delivery',
            default => ucfirst($payment->payment_method),
        };
    }
}
