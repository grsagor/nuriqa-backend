<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

class ContactController extends Controller
{
    public function index()
    {
        return view('backend.pages.contacts.index');
    }
    
    public function list()
    {
        if (request()->ajax()) {
            $data = Contact::select('id', 'first_name', 'last_name', 'email', 'phone', 'subject', 'is_read', 'created_at')
                ->latest();

            return DataTables::of($data)
                ->addColumn('name', function ($row) {
                    return $row->first_name . ' ' . $row->last_name;
                })
                ->addColumn('is_read', function ($row) {
                    $badgeClass = $row->is_read ? 'bg-success' : 'bg-warning';
                    $text = $row->is_read ? 'Read' : 'Unread';
                    return '<span class="badge ' . $badgeClass . '">' . $text . '</span>';
                })
                ->addColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d M Y H:i');
                })
                ->addColumn('action', function ($row) {
                    $view = '<button data-url="' . route('admin.contacts.show', $row->id) . '" data-modal-parent="#crudModal" class="btn btn-sm btn-info open_modal_btn"><i class="fas fa-eye"></i></button>';
                    $delete = '<button data-url="' . route('admin.contacts.delete', $row->id) . '" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';
                    
                    return $view . ' ' . $delete;
                })
                ->rawColumns(['is_read', 'action'])
                ->make(true);
        }

        return abort(404);
    }
    
    public function show($id)
    {
        $contact = Contact::find($id);
        if (!$contact) {
            return abort(404);
        }
        
        // Mark as read
        if (!$contact->is_read) {
            $contact->update(['is_read' => true]);
        }
        
        $html = view('backend.pages.contacts.show', compact('contact'))->render();
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    
    public function delete($id)
    {
        $contact = Contact::find($id);
        if (!$contact) {
            return abort(404);
        }
        
        $contact->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Contact message deleted successfully'
        ]);
    }
}
