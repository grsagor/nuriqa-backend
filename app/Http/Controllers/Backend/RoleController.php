<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class RoleController extends Controller
{
    public function index()
    {
        return view('backend.pages.roles.index');
    }
    public function create()
    {
        $html = view('backend.pages.roles.create')->render();
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name'
        ]);

        Role::create([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully'
        ]);
    }
    public function list()
    {
        if (request()->ajax()) {
            $data = Role::select('id', 'name')->latest();

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
        $role = Role::find($id);
        if (!$role) {
            return abort(404);
        }
        $html = view('backend.pages.roles.edit', compact('role'))->render();
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $request->id
        ]);

        Role::find($request->id)->update([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully'
        ]);
    }
    public function delete($id)
    {
        $role = Role::find($id);
        if (!$role) {
            return abort(404);
        }
        $role->delete();
        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully'
        ]);
    }
}
