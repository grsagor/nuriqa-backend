<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class CategoryController extends Controller
{
    public function index()
    {
        return view('backend.pages.categories.index');
    }
    public function create()
    {
        $html = view('backend.pages.categories.create')->render();
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name'
        ]);

        Category::create([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully'
        ]);
    }
    public function list()
    {
        if (request()->ajax()) {
            $data = Category::select('id', 'name')->latest();

            return DataTables::of($data)

                ->addColumn('action', function ($row) {
                    $edit = '<button data-url="' . route('admin.categories.edit', $row->id) . '" data-modal-parent="#crudModal" class="btn btn-sm btn-primary open_modal_btn" data-modal-parent="#crudModal"><i class="fas fa-edit"></i></button>';
                    $delete = '<button data-url="' . route('admin.categories.delete', $row->id) . '" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                    return $edit . ' ' . $delete;
                })

                ->rawColumns(['action'])

                ->make(true);
        }

        return abort(404);
    }
    public function edit($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return abort(404);
        }
        $html = view('backend.pages.categories.edit', compact('category'))->render();
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $request->id
        ]);

        Category::find($request->id)->update([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully'
        ]);
    }
    public function delete($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return abort(404);
        }
        $category->delete();
        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }
}