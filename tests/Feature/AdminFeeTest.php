<?php

namespace Tests\Feature;

use App\Services\PlatformFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_admin_fee_is_seventy_five_pence(): void
    {
        \App\Models\PlatformSetting::query()->update(['admin_fee_amount' => 0.75]);
        PlatformFeeService::clearCache();

        $this->assertSame(0.75, PlatformFeeService::adminFeeAmount());
        $this->assertSame(0.75, PlatformFeeService::DEFAULT_ADMIN_FEE_AMOUNT);
    }
}
