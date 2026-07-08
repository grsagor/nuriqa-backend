<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ImageService;
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
            'html' => $html,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = ['name' => $request->name];

        if ($request->hasFile('image')) {
            $data['image'] = ImageService::upload($request->file('image'), 'categories');
        }

        Category::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
        ]);
    }

    public function list()
    {
        if (request()->ajax()) {
            $data = Category::select('id', 'name', 'image')->latest();

            return DataTables::of($data)
                ->addColumn('image', function ($row) {
                    $imagePath = ImageService::getUrl($row->image, asset('assets/img/utils/no-image.png'));
                    if ($row->image) {
                        return '<img src="'.$imagePath.'" alt="'.$row->name.'" class="user-avatar">';
                    }

                    return '<div class="user-avatar d-flex align-items-center justify-content-center bg-light"><i class="fas fa-image text-muted"></i></div>';
                })
                ->addColumn('action', function ($row) {
                    $edit = '<button data-url="'.route('admin.categories.edit', $row->id).'" data-modal-parent="#crudModal" class="btn btn-sm btn-primary open_modal_btn" data-modal-parent="#crudModal"><i class="fas fa-edit"></i></button>';
                    $delete = '<button data-url="'.route('admin.categories.delete', $row->id).'" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                    return $edit.' '.$delete;
                })
                ->rawColumns(['image', 'action'])
                ->make(true);
        }

        return abort(404);
    }

    public function edit($id)
    {
        $category = Category::find($id);
        if (! $category) {
            return abort(404);
        }
        $html = view('backend.pages.categories.edit', compact('category'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,'.$request->id,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $category = Category::find($request->id);
        $data = ['name' => $request->name];

        if ($request->has('remove_image') && $request->remove_image == '1') {
            if ($category->image) {
                ImageService::delete($category->image);
                $data['image'] = null;
            }
        } elseif ($request->hasFile('image')) {
            $data['image'] = ImageService::upload($request->file('image'), 'categories', $category->image);
        }

        $category->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
        ]);
    }

    public function delete($id)
    {
        $category = Category::find($id);
        if (! $category) {
            return abort(404);
        }

        if ($category->image) {
            ImageService::delete($category->image);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }
}
