<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\TraderDocument;
use App\Models\TraderVerification;
use App\Services\AuditLogService;
use App\Services\TraderVerificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class TraderVerificationController extends Controller
{
    private const BADGES = [
        TraderVerification::PENDING => 'warning',
        TraderVerification::APPROVED => 'success',
        TraderVerification::REJECTED => 'danger',
        TraderVerification::DRAFT => 'secondary',
    ];

    public function __construct(
        protected TraderVerificationService $service,
        protected AuditLogService $auditLogService,
    ) {}

    public function index()
    {
        return view('backend.pages.trader-verifications.index');
    }

    public function list(Request $request)
    {
        if (! $request->ajax()) {
            return abort(404);
        }

        $status = $request->query('status');
        $data = TraderVerification::query()
            ->with('user:id,name,email')
            // Drafts are works in progress, so they only show when asked for.
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when(! $status || $status === 'all', fn ($q) => $q->where('status', '!=', TraderVerification::DRAFT))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('submitted_at');

        return DataTables::of($data)
            ->addColumn('trader', fn ($row) => e($row->user?->name ?? '—').'<div class="small text-muted">'.e($row->user?->email).'</div>')
            ->addColumn('business', fn ($row) => e($row->legal_name ?? '—').'<div class="small text-muted">'.e(str_replace('_', ' ', (string) $row->business_type)).'</div>')
            ->addColumn('status', fn ($row) => '<span class="badge bg-'.self::BADGES[$row->status].'">'.e(ucfirst($row->status)).'</span>')
            ->addColumn('submitted_at', fn ($row) => $row->submitted_at ? Carbon::parse($row->submitted_at)->format('d M Y H:i') : '—')
            ->addColumn('action', fn ($row) => '<button data-url="'.route('admin.trader-verifications.show', $row->id).'" data-modal-parent="#crudModal" class="btn btn-sm btn-info open_modal_btn"><i class="fas fa-eye"></i></button>')
            ->rawColumns(['trader', 'business', 'status', 'action'])
            ->make(true);
    }

    public function show(string $id)
    {
        $verification = TraderVerification::with(['user.role', 'documents', 'reviewer:id,name'])->findOrFail($id);

        $categories = Category::query()->whereIn('id', $verification->product_category_ids ?? [])->pluck('name')->all();
        $history = AuditLog::query()
            ->with('actor:id,name')
            ->where('auditable_type', $verification->getMorphClass())
            ->where('auditable_id', $verification->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'html' => view('backend.pages.trader-verifications.show', [
                'v' => $verification,
                'categories' => $categories,
                'history' => $history,
                'documentTypeLabels' => [
                    TraderDocument::IDENTITY => 'Owner / director identity',
                    TraderDocument::BANK_PROOF => 'Bank account proof',
                    TraderDocument::PRODUCT_EVIDENCE => 'Evidence products are genuine',
                    TraderDocument::BUSINESS_PROOF => 'Business registration / VAT',
                ],
            ])->render(),
        ]);
    }

    public function approve(Request $request, string $id)
    {
        $verification = TraderVerification::findOrFail($id);
        $data = $request->validate(['internal_notes' => ['nullable', 'string', 'max:2000']]);

        try {
            $this->service->approve($verification, $request->user(), $data['internal_notes'] ?? null);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Trader approved. They can now buy and sell.']);
    }

    public function reject(Request $request, string $id)
    {
        $verification = TraderVerification::findOrFail($id);
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->service->reject($verification, $request->user(), $data['rejection_reason'], $data['internal_notes'] ?? null);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Application rejected. The trader has been told why and can resubmit.']);
    }

    /** Admin-only download of an uploaded document; every access is recorded. */
    public function document(Request $request, string $id)
    {
        $document = TraderDocument::with('verification')->findOrFail($id);

        abort_unless(Storage::disk(TraderVerificationService::STORAGE_DISK)->exists($document->path), 404);

        $this->auditLogService->record(
            'trader_verification.document_viewed',
            $document->verification,
            $request->user(),
            null,
            null,
            $document->original_name,
            ['document_id' => $document->id, 'type' => $document->type],
        );

        return Storage::disk(TraderVerificationService::STORAGE_DISK)->download($document->path, $document->original_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
