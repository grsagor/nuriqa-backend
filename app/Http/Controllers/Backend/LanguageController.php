<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class LanguageController extends Controller
{
    public function index()
    {
        return view('backend.pages.languages.index');
    }

    public function create()
    {
        $html = view('backend.pages.languages.create')->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:languages,name',
            'code' => 'required|string|max:10|unique:languages,code',
        ]);

        Language::create([
            'name' => $request->name,
            'code' => $request->code,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Language created successfully',
        ]);
    }

    public function list()
    {
        if (request()->ajax()) {
            $data = Language::select('id', 'name', 'code', 'is_active', 'created_at')->latest();

            return DataTables::of($data)
                ->addColumn('is_active', function ($row) {
                    if ($row->is_active) {
                        return '<span class="badge bg-success">Active</span>';
                    } else {
                        return '<span class="badge bg-secondary">Inactive</span>';
                    }
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('d M Y');
                })
                ->addColumn('action', function ($row) {
                    $edit = '<button data-url="'.route('admin.languages.edit', $row->id).'" data-modal-parent="#crudModal" class="btn btn-sm btn-primary open_modal_btn" data-modal-parent="#crudModal"><i class="fas fa-edit"></i></button>';
                    $delete = '<button data-url="'.route('admin.languages.delete', $row->id).'" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                    return $edit.' '.$delete;
                })
                ->rawColumns(['is_active', 'action'])
                ->make(true);
        }

        return abort(404);
    }

    public function edit($id)
    {
        $language = Language::find($id);
        if (! $language) {
            return abort(404);
        }
        $html = view('backend.pages.languages.edit', compact('language'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:languages,name,'.$request->id,
            'code' => 'required|string|max:10|unique:languages,code,'.$request->id,
        ]);

        Language::find($request->id)->update([
            'name' => $request->name,
            'code' => $request->code,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Language updated successfully',
        ]);
    }

    public function delete($id)
    {
        $language = Language::find($id);
        if (! $language) {
            return abort(404);
        }
        $language->delete();

        return response()->json([
            'success' => true,
            'message' => 'Language deleted successfully',
        ]);
    }
}
