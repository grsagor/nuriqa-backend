<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  ...$roles
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('auth.login');
        }

        $user = Auth::user();
        $hasAccess = false;
        
        // Create role mapping for easier checking
        $roleMapping = [
            'admin' => 1,
            'seller' => 2,
            'customer' => 3,
        ];
        
        // Check if user has any of the required roles
        foreach ($roles as $role) {
            $role = strtolower($role);
            
            // Check predefined roles
            if (isset($roleMapping[$role])) {
                if ($user->role_id == $roleMapping[$role]) {
                    $hasAccess = true;
                    break;
                }
            } else {
                // Check by role name if role doesn't match predefined cases
                if ($user->role && strtolower($user->role->name) === $role) {
                    $hasAccess = true;
                    break;
                }
            }
        }
        
        if (!$hasAccess) {
            $rolesList = implode(', ', $roles);
            return redirect()->route('dashboard')->with('error', "You do not have the required privileges. Required roles: {$rolesList}");
        }

        return $next($request);
    }
}