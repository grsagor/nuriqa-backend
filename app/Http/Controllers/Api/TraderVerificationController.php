<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TraderDocument;
use App\Models\TraderVerification;
use App\Services\TraderVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class TraderVerificationController extends Controller
{
    private const MAX_FILES_PER_TYPE = 5;

    public function __construct(protected TraderVerificationService $service) {}

    private function verificationFor(): TraderVerification
    {
        $user = JWTAuth::parseToken()->authenticate();

        // user_id and status are guarded against mass assignment, so the row is created explicitly.
        return TraderVerification::query()->where('user_id', $user->id)->first()
            ?? TraderVerification::forceCreate(['user_id' => $user->id, 'status' => TraderVerification::DRAFT]);
    }

    private function locked(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'This application is under review or already verified, so it can no longer be edited.',
        ], 423);
    }

    public function show(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->service->present($this->verificationFor())]);
    }

    /** Save progress. Anything provided must be valid, but nothing is required until the application is submitted. */
    public function update(Request $request): JsonResponse
    {
        $verification = $this->verificationFor();
        if (! $verification->isEditable()) {
            return $this->locked();
        }

        // Tidy first (so "12-34-56" or "gb 123 456 789" are accepted as people type them), then validate what is stored.
        $input = TraderVerificationService::normalize($request->only(TraderVerification::FILLABLE_BY_TRADER));
        $validated = Validator::make($input, TraderVerificationService::draftRules(), [], TraderVerificationService::attributeNames())->validate();

        $verification->fill($validated)->save();

        return response()->json(['success' => true, 'data' => $this->service->present($verification->fresh())]);
    }

    public function submit(Request $request): JsonResponse
    {
        $verification = $this->verificationFor();
        if (! $verification->isEditable()) {
            return $this->locked();
        }

        $this->service->submit($verification, JWTAuth::parseToken()->authenticate());

        return response()->json([
            'success' => true,
            'message' => 'Your application has been submitted. We will review it and let you know.',
            'data' => $this->service->present($verification->fresh()),
        ]);
    }

    public function uploadDocument(Request $request): JsonResponse
    {
        $verification = $this->verificationFor();
        if (! $verification->isEditable()) {
            return $this->locked();
        }

        $validator = Validator::make($request->all(), [
            'type' => ['required', 'in:'.implode(',', TraderDocument::TYPES)],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,heic,webp', 'mimetypes:application/pdf,image/jpeg,image/png,image/heic,image/webp'],
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $type = $request->input('type');
        if ($verification->documents()->where('type', $type)->count() >= self::MAX_FILES_PER_TYPE) {
            return response()->json([
                'success' => false,
                'message' => 'You can upload up to '.self::MAX_FILES_PER_TYPE.' files for each document type.',
            ], 422);
        }

        $file = $request->file('file');
        // Random name on a private disk: never the client's file name, never a public URL.
        $path = $file->storeAs(
            'trader-verification/'.$verification->user_id,
            Str::uuid()->toString().'.'.$file->guessExtension(),
            TraderVerificationService::STORAGE_DISK
        );

        $document = $verification->documents()->create([
            'type' => $type,
            'path' => $path,
            'original_name' => Str::limit(basename($file->getClientOriginalName()), 200, ''),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json(['success' => true, 'data' => $this->service->present($verification->fresh()), 'document_id' => $document->id], 201);
    }

    public function deleteDocument(int $id): JsonResponse
    {
        $verification = $this->verificationFor();
        if (! $verification->isEditable()) {
            return $this->locked();
        }

        $document = $verification->documents()->findOrFail($id);
        $this->service->deleteDocumentFile($document);
        $document->delete();

        return response()->json(['success' => true, 'data' => $this->service->present($verification->fresh())]);
    }
}
