<?php

namespace Tests\Feature\Settings;

use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_values_keep_their_type_and_reads_are_cached(): void
    {
        $this->assertSame(15, AppSetting::get('orders.auto_close_days', 15));

        AppSetting::set('orders.auto_close_days', 30);
        AppSetting::set('features', ['beta' => true]);
        AppSetting::set('nullable', null);

        $this->assertSame(30, AppSetting::get('orders.auto_close_days'));
        $this->assertSame(['beta' => true], AppSetting::get('features'));
        $this->assertNull(AppSetting::get('nullable', 'default'));

        DB::enableQueryLog();
        AppSetting::get('orders.auto_close_days');
        $this->assertCount(0, DB::getQueryLog());

        AppSetting::forget('orders.auto_close_days');
        $this->assertSame(15, AppSetting::get('orders.auto_close_days', 15));
    }
}
