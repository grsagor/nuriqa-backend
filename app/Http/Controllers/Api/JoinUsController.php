<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\JoinUsRequest;
use App\Models\JoinUsApplication;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class JoinUsController extends Controller
{
    public function store(JoinUsRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Handle file uploads
            if ($request->hasFile('images')) {
                $imagePaths = [];
                foreach ($request->file('images') as $image) {
                    $path = ImageService::upload($image, 'join-us/images');
                    if ($path) {
                        $imagePaths[] = $path;
                    }
                }
                $data['model_images'] = $imagePaths;
            }

            if ($request->hasFile('cv')) {
                $cvPath = ImageService::upload($request->file('cv'), 'join-us/cv');
                if ($cvPath) {
                    $data['cv_path'] = $cvPath;
                }
            }

            // Handle JSON fields - decode if they come as JSON strings
            $jsonFields = ['comfort_preferences', 'model_experiences', 'areas_of_interest', 'availability', 'commitment_level', 'agreements'];
            foreach ($jsonFields as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    $decoded = json_decode($data[$field], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data[$field] = $decoded;
                    }
                }
            }

            // Create application
            $application = JoinUsApplication::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully',
                'data' => $application,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Join Us Application Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit application. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
