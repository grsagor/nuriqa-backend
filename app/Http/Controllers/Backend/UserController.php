<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Language;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index()
    {
        return view('backend.pages.users.index');
    }
    
    public function create()
    {
        $roles = Role::pluck('name', 'id');
        $languages = Language::pluck('name', 'id');
        $html = view('backend.pages.users.create', compact('roles', 'languages'))->render();
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'signup_date' => 'nullable|date',
            'lang_id' => 'nullable|exists:languages,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        $data = $request->except(['password_confirmation', 'image']);
        
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
        
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/users', $imageName);
            $data['image'] = $imageName;
        }
        
        if ($request->filled('signup_date')) {
            $data['email_verified_at'] = now();
        }

        User::create($data);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully'
        ]);
    }
    
    public function list()
    {
        if (request()->ajax()) {
            $data = User::with(['role', 'language'])->select('id', 'name', 'email', 'phone', 'image', 'role_id', 'created_at')->latest();

            return DataTables::of($data)
                ->addColumn('image', function ($row) {
                    $imagePath = $row->image ? Storage::url('users/' . $row->image) : asset('assets/img/default-avatar.png');
                    if ($row->image) {
                        return '<img src="' . $imagePath . '" alt="' . $row->name . '" class="user-avatar">';
                    }
                    return '<div class="user-avatar d-flex align-items-center justify-content-center bg-light"><i class="fas fa-user text-muted"></i></div>';
                })
                ->addColumn('name', function ($row) {
                    return '<div class="d-flex align-items-center"><i class="fas fa-user me-2 text-primary"></i>' . $row->name . '</div>';
                })
                ->addColumn('phone', function ($row) {
                    return $row->phone ?: '<span class="text-muted">N/A</span>';
                })
                ->addColumn('role', function ($row) {
                    $roleName = $row->role ? $row->role->name : 'N/A';
                    return '<span class="badge bg-primary">' . $roleName . '</span>';
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('d M Y');
                })
                ->addColumn('action', function ($row) {
                    $edit = '<button data-url="' . route('admin.users.edit', $row->id) . '" data-modal-parent="#crudModal" class="btn btn-sm btn-primary open_modal_btn"><i class="fas fa-edit"></i></button>';
                    $delete = '<button data-url="' . route('admin.users.delete', $row->id) . '" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                    return $edit . ' ' . $delete;
                })
                ->rawColumns(['image', 'name', 'phone', 'role', 'action'])

                ->make(true);
        }

        return abort(404);
    }
    
    public function edit($id)
    {
        $user = User::find($id);
        if (!$user) {
            return abort(404);
        }
        
        $roles = Role::pluck('name', 'id');
        $languages = Language::pluck('name', 'id');
        
        $html = view('backend.pages.users.edit', compact('user', 'roles', 'languages'))->render();
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $request->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'signup_date' => 'nullable|date',
            'lang_id' => 'nullable|exists:languages,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        $user = User::find($request->id);
        $data = $request->except(['password_confirmation', 'image']);
        
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }
        
        if ($request->hasFile('image')) {
            // Delete old image
            if ($user->image) {
                Storage::delete('public/users/' . $user->image);
            }
            
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/users', $imageName);
            $data['image'] = $imageName;
        }
        
        if ($request->filled('signup_date')) {
            $data['email_verified_at'] = now();
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully'
        ]);
    }
    
    public function delete($id)
    {
        $user = User::find($id);
        if (!$user) {
            return abort(404);
        }
        
        // Delete user image
        if ($user->image) {
            Storage::delete('public/users/' . $user->image);
        }
        
        $user->delete();
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
