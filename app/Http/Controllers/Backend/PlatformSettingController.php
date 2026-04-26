<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\PlatformFeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSettingController extends Controller
{
    public function index(): View
    {
        $settings = PlatformSetting::query()->firstOrFail();

        return view('backend.pages.platform-settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fee_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $settings = PlatformSetting::query()->firstOrFail();
        $settings->update([
            'fee_percentage' => $validated['fee_percentage'],
        ]);
        PlatformFeeService::clearCache();

        return redirect()
            ->route('admin.platform-settings.index')
            ->with('success', 'Buyer protection fee updated successfully.');
    }
}
