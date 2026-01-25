<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * User Signup
     */
    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
            'role_id' => 'nullable|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->all();
            $data['password'] = Hash::make($request->password);

            // Set default role if not provided
            if (! isset($data['role_id']) || empty($data['role_id'])) {
                $defaultRole = Role::where('name', 'user')->first();
                $data['role_id'] = $defaultRole ? $defaultRole->id : null;
            }

            $user = User::create($data);

            $otpCode = OtpService::generate($user->email);

            $debugOtpData = config('app.debug') ? ['otp_code' => $otpCode] : [];

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully. Please verify your email with the OTP sent.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role ? $user->role->name : null,
                        'role_id' => $user->role_id,
                        'created_at' => $user->created_at->toISOString(),
                    ],
                    ...$debugOtpData,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * User Login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $credentials = $request->only('email', 'password');

            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            $user = Auth::user();

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'image_url' => $user->image_url,
                        'role' => $user->role ? $user->role->name : null,
                        'role_id' => $user->role_id,
                        'created_at' => $user->created_at->toISOString(),
                    ],
                    'token' => $token,
                    'expires_in' => JWTAuth::factory()->getTTL() * 60, // in seconds
                    'token_type' => 'Bearer',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * User Logout
     */
    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $email = $request->email;
            $otp = $request->otp;

            // Get the user first
            $user = User::where('email', $email)->first();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            if ($user->email_verified_at) {
                $token = JWTAuth::fromUser($user);

                return response()->json([
                    'success' => true,
                    'message' => 'Email already verified',
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'role' => $user->role ? $user->role->name : null,
                            'role_id' => $user->role_id,
                            'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->toISOString() : null,
                            'created_at' => $user->created_at->toISOString(),
                        ],
                        'token' => $token,
                        'expires_in' => JWTAuth::factory()->getTTL() * 60,
                        'token_type' => 'Bearer',
                    ],
                ]);
            }

            if (! OtpService::verify($email, $otp)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP',
                ], 400);
            }

            $user->refresh();
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role ? $user->role->name : null,
                        'role_id' => $user->role_id,
                        'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->toISOString() : null,
                        'created_at' => $user->created_at->toISOString(),
                    ],
                    'token' => $token,
                    'expires_in' => JWTAuth::factory()->getTTL() * 60,
                    'token_type' => 'Bearer',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'OTP verification failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function myUserInfo(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }
}
