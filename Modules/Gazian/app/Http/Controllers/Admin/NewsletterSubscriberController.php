<?php

namespace Modules\Gazian\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Gazian\Models\NewsletterSubscriber;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\DataTables;

class NewsletterSubscriberController extends Controller
{
    public function index()
    {
        return view('gazian::pages.newsletter-subscribers.index');
    }

    public function exportCsv(): StreamedResponse
    {
        $filename = 'gazian-newsletter-subscribers-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['id', 'email', 'subscribed_at']);

            NewsletterSubscriber::query()
                ->orderBy('id')
                ->chunk(500, function ($subscribers) use ($handle): void {
                    foreach ($subscribers as $subscriber) {
                        fputcsv($handle, [
                            $subscriber->id,
                            $subscriber->email,
                            $subscriber->created_at?->format('Y-m-d H:i:s') ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function list()
    {
        if (! request()->ajax()) {
            return abort(404);
        }

        $data = NewsletterSubscriber::query()
            ->select('id', 'email', 'created_at')
            ->latest();

        return DataTables::of($data)
            ->addColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('d M Y H:i');
            })
            ->addColumn('action', function ($row) {
                return '<button data-url="'.route('gazian.admin.newsletter-subscribers.delete', $row->id).'" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function delete(int $id)
    {
        $subscriber = NewsletterSubscriber::query()->find($id);
        if ($subscriber === null) {
            return response()->json([
                'success' => false,
                'message' => 'Subscriber not found',
            ], 404);
        }

        $subscriber->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subscriber deleted successfully',
        ]);
    }
}
