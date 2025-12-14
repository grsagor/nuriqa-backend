<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class BrandController extends Controller
{
    public function index()
    {
        return view('backend.pages.brands.index');
    }
    public function create()
    {
        $html = view('backend.pages.brands.create')->render();
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:brands,name'
        ]);

        Brand::create([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Brand created successfully'
        ]);
    }
    public function list()
    {
        if (request()->ajax()) {
            $data = Brand::select('id', 'name')->latest();

            return DataTables::of($data)

                ->addColumn('action', function ($row) {
                    $edit = '<button data-url="' . route('admin.brands.edit', $row->id) . '" data-modal-parent="#crudModal" class="btn btn-sm btn-primary open_modal_btn" data-modal-parent="#crudModal"><i class="fas fa-edit"></i></button>';
                    $delete = '<button data-url="' . route('admin.brands.delete', $row->id) . '" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                    return $edit . ' ' . $delete;
                })

                ->rawColumns(['action'])

                ->make(true);
        }

        return abort(404);
    }
    public function edit($id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return abort(404);
        }
        $html = view('backend.pages.brands.edit', compact('brand'))->render();
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,' . $request->id
        ]);

        Brand::find($request->id)->update([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Brand updated successfully'
        ]);
    }
    public function delete($id)
    {
        $brand = Brand::find($id);
        if (!$brand) {
            return abort(404);
        }
        $brand->delete();
        return response()->json([
            'success' => true,
            'message' => 'Brand deleted successfully'
        ]);
    }
}