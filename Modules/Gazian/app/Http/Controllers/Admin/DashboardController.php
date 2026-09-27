<?php

namespace Modules\Gazian\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Gazian\Models\NewsletterSubscriber;
use Modules\Gazian\Models\TradeEnquiry;

class DashboardController extends Controller
{
    public function index()
    {
        $newsletterCount = NewsletterSubscriber::query()->count();
        $enquiryCount = TradeEnquiry::query()->count();
        $unreadEnquiryCount = TradeEnquiry::query()->where('is_read', false)->count();

        return view('gazian::pages.dashboard.index', compact(
            'newsletterCount',
            'enquiryCount',
            'unreadEnquiryCount',
        ));
    }
}
