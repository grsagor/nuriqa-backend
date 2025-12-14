<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function login()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function loginSubmit(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            
            return redirect()->intended(route('admin.dashboard.index'))->with('success', 'You have successfully logged in!');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email', 'remember'));
    }

    /**
     * Show the registration form
     */
    public function register()
    {
        $roles = \App\Models\Role::pluck('name', 'id');
        $languages = \App\Models\Language::where('is_active', 1)->pluck('name', 'id');
        
        return view('auth.register', compact('roles', 'languages'));
    }

    /**
     * Handle registration request
     */
    public function registerSubmit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'lang_id' => 'nullable|exists:languages,id',
            'role_id' => 'nullable|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->except(['password_confirmation', 'image']);
        $data['password'] = Hash::make($request->password);
        $data['signup_date'] = now();
        
        // Set default role if not provided
        if (empty($data['role_id'])) {
            $data['role_id'] = 2; // Assuming 2 is the default user role
        }
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image'] = ImageService::upload($request->file('image'), 'users');
        }

        $user = User::create($data);

        // Log in the user after registration
        Auth::login($user);

        return redirect()->route('admin.dashboard.index')->with('success', 'Registration successful! You are now logged in.');
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('auth.login')->with('success', 'You have been logged out successfully.');
    }
}
