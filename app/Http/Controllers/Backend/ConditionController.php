<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Condition;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ConditionController extends Controller
{
    public function index()
    {
        return view('backend.pages.conditions.index');
    }

    public function create()
    {
        $html = view('backend.pages.conditions.create')->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:conditions,name',
        ]);

        Condition::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Condition created successfully',
        ]);
    }

    public function list()
    {
        if (request()->ajax()) {
            $data = Condition::select('id', 'name')->latest();

            return DataTables::of($data)

                ->addColumn('action', function ($row) {
                    $edit = '<button data-url="'.route('admin.conditions.edit', $row->id).'" data-modal-parent="#crudModal" class="btn btn-sm btn-primary open_modal_btn" data-modal-parent="#crudModal"><i class="fas fa-edit"></i></button>';
                    $delete = '<button data-url="'.route('admin.conditions.delete', $row->id).'" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                    return $edit.' '.$delete;
                })

                ->rawColumns(['action'])

                ->make(true);
        }

        return abort(404);
    }

    public function edit($id)
    {
        $condition = Condition::find($id);
        if (! $condition) {
            return abort(404);
        }
        $html = view('backend.pages.conditions.edit', compact('condition'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:conditions,name,'.$request->id,
        ]);

        Condition::find($request->id)->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Condition updated successfully',
        ]);
    }

    public function delete($id)
    {
        $condition = Condition::find($id);
        if (! $condition) {
            return abort(404);
        }
        $condition->delete();

        return response()->json([
            'success' => true,
            'message' => 'Condition deleted successfully',
        ]);
    }
}
