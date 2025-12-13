<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{


    public function index()
    {
        return view('backend.pages.users.index');
    }
    public function create()
    {
        $html = view('backend.pages.users.create')->render();
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:users,name'
        ]);

        User::create([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully'
        ]);
    }
    public function list()
    {
        if (request()->ajax()) {
            $data = User::select('id', 'name')->latest();

            return DataTables::of($data)

                ->addColumn('action', function ($row) {
                    $edit = '<button data-url="' . route('admin.roles.edit', $row->id) . '" data-modal-parent="#crudModal" class="btn btn-sm btn-primary open_modal_btn" data-modal-parent="#crudModal"><i class="fas fa-edit"></i></button>';
                    $delete = '<button data-url="' . route('admin.roles.delete', $row->id) . '" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                    return $edit . ' ' . $delete;
                })

                ->rawColumns(['action'])

                ->make(true);
        }

        return abort(404);
    }
    public function edit($id)
    {
        $role = User::find($id);
        if (!$role) {
            return abort(404);
        }
        $html = view('backend.pages.users.edit', compact('role'))->render();
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:users,name,' . $request->id
        ]);

        User::find($request->id)->update([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully'
        ]);
    }
    public function delete($id)
    {
        $role = User::find($id);
        if (!$role) {
            return abort(404);
        }
        $role->delete();
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
