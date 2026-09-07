<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\AddSupportCaseMessageRequest;
use App\Http\Requests\Backend\AssignSupportCaseRequest;
use App\Http\Requests\Backend\DecideSupportCaseRequest;
use App\Models\SupportCase;
use App\Models\User;
use App\Services\SupportCaseService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;

class SupportCaseController extends Controller
{
    public function __construct(protected SupportCaseService $supportCaseService) {}

    public function index(): View
    {
        return view('backend.pages.support-cases.index');
    }

    public function list()
    {
        if (! request()->ajax()) {
            return abort(404);
        }

        $data = SupportCase::query()
            ->with(['user:id,name,email', 'owner:id,name,email'])
            ->latest();

        return DataTables::of($data)
            ->addColumn('case_number', fn ($row) => e($row->case_number))
            ->addColumn('type', fn ($row) => e(ucfirst($row->type)))
            ->addColumn('subject', fn ($row) => e(\Illuminate\Support\Str::limit($row->subject, 60)))
            ->addColumn('customer', function ($row) {
                return e($row->user?->name ?? '—').'<br><small class="text-muted">'.e($row->user?->email ?? '').'</small>';
            })
            ->addColumn('owner_name', fn ($row) => e($row->owner?->name ?? 'Unassigned'))
            ->addColumn('status', function ($row) {
                $map = [
                    SupportCase::STATUS_SUBMITTED => 'warning',
                    SupportCase::STATUS_ACKNOWLEDGED => 'info',
                    SupportCase::STATUS_ASSIGNED => 'primary',
                    SupportCase::STATUS_IN_PROGRESS => 'info',
                    SupportCase::STATUS_AWAITING_CUSTOMER => 'secondary',
                    SupportCase::STATUS_RESOLVED => 'success',
                    SupportCase::STATUS_CLOSED => 'dark',
                ];
                $cls = $map[$row->status] ?? 'secondary';

                return '<span class="badge bg-'.$cls.'">'.e(str_replace('_', ' ', ucfirst($row->status))).'</span>';
            })
            ->addColumn('created_at', fn ($row) => Carbon::parse($row->created_at)->format('d M Y H:i'))
            ->addColumn('action', function ($row) {
                return '<a href="'.route('admin.support-cases.show', $row->id).'" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>';
            })
            ->rawColumns(['customer', 'status', 'action'])
            ->make(true);
    }

    public function show(int $id): View
    {
        $case = SupportCase::query()
            ->with([
                'user:id,name,email',
                'owner:id,name,email',
                'product:id,title',
                'transaction:id',
                'messages.user:id,name',
            ])
            ->findOrFail($id);

        $admins = User::query()
            ->where('role_id', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('backend.pages.support-cases.show', compact('case', 'admins'));
    }

    public function acknowledge(Request $request, int $id): RedirectResponse
    {
        $case = SupportCase::query()->findOrFail($id);
        $this->supportCaseService->acknowledge($case, $request->user());

        return back()->with('success', 'Case acknowledged.');
    }

    public function assign(AssignSupportCaseRequest $request, int $id): RedirectResponse
    {
        $case = SupportCase::query()->findOrFail($id);
        $owner = User::query()->where('role_id', 1)->findOrFail($request->validated('owner_id'));
        $this->supportCaseService->assign($case, $owner);

        return back()->with('success', 'Case assigned to '.$owner->name.'.');
    }

    public function addMessage(AddSupportCaseMessageRequest $request, int $id): RedirectResponse
    {
        $case = SupportCase::query()->findOrFail($id);
        $data = $request->validated();

        $this->supportCaseService->addMessage(
            $case,
            $request->user(),
            $data['body'],
            (bool) ($data['is_internal'] ?? false)
        );

        if (! empty($data['awaiting_customer'])) {
            $case->update(['status' => SupportCase::STATUS_AWAITING_CUSTOMER]);
        }

        return back()->with('success', 'Message added.');
    }

    public function decide(DecideSupportCaseRequest $request, int $id): RedirectResponse
    {
        $case = SupportCase::query()->findOrFail($id);
        $this->supportCaseService->decide($case, $request->validated(), $request->user());

        return back()->with('success', 'Case decision saved.');
    }
}
