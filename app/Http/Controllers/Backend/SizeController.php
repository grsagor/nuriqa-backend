<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Size;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class SizeController extends Controller
{
    public function index()
    {
        return view('backend.pages.sizes.index');
    }
    public function create()
    {
        $html = view('backend.pages.sizes.create')->render();
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sizes,name'
        ]);

        Size::create([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Size created successfully'
        ]);
    }
    public function list()
    {
        if (request()->ajax()) {
            $data = Size::select('id', 'name')->latest();

            return DataTables::of($data)

                ->addColumn('action', function ($row) {
                    $edit = '<button data-url="' . route('admin.sizes.edit', $row->id) . '" data-modal-parent="#crudModal" class="btn btn-sm btn-primary open_modal_btn" data-modal-parent="#crudModal"><i class="fas fa-edit"></i></button>';
                    $delete = '<button data-url="' . route('admin.sizes.delete', $row->id) . '" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                    return $edit . ' ' . $delete;
                })

                ->rawColumns(['action'])

                ->make(true);
        }

        return abort(404);
    }
    public function edit($id)
    {
        $size = Size::find($id);
        if (!$size) {
            return abort(404);
        }
        $html = view('backend.pages.sizes.edit', compact('size'))->render();
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sizes,name,' . $request->id
        ]);

        Size::find($request->id)->update([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Size updated successfully'
        ]);
    }
    public function delete($id)
    {
        $size = Size::find($id);
        if (!$size) {
            return abort(404);
        }
        $size->delete();
        return response()->json([
            'success' => true,
            'message' => 'Size deleted successfully'
        ]);
    }
}