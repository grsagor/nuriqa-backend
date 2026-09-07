<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportCase;
use App\Services\SupportCaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class SupportCaseController extends Controller
{
    public function __construct(protected SupportCaseService $supportCaseService) {}

    public function index(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $cases = SupportCase::query()
            ->where('user_id', $user->id)
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->with(['messages' => fn ($q) => $q->where('is_internal', false)->latest()])
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $cases]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $data = $request->validate([
            'type' => 'required|in:damage,dispute,query',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'transaction_id' => 'nullable|exists:transactions,id',
            'product_id' => 'nullable|exists:products,id',
            'evidence' => 'nullable|array',
            'evidence.*' => 'nullable|string|max:2048',
        ]);

        if (! empty($data['transaction_id'])) {
            $owns = \App\Models\Transaction::query()
                ->where('id', $data['transaction_id'])
                ->where('user_id', $user->id)
                ->exists();
            if (! $owns) {
                return response()->json(['message' => 'Transaction not found.'], 404);
            }
        }

        $case = $this->supportCaseService->create($user, $data);

        return response()->json([
            'success' => true,
            'message' => 'Your request has been received.',
            'data' => $case,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $case = SupportCase::query()
            ->where('user_id', $user->id)
            ->with(['messages' => fn ($q) => $q->where('is_internal', false)->oldest(), 'product', 'transaction'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $case]);
    }

    public function addMessage(Request $request, int $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
        $case = SupportCase::query()->where('user_id', $user->id)->findOrFail($id);

        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $message = $this->supportCaseService->addMessage($case, $user, $data['body']);

        return response()->json(['success' => true, 'data' => $message], 201);
    }
}
