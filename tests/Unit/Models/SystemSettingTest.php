<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SystemSettingTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_system_setting()
    {
        $setting = SystemSetting::create([
            'key' => 'app_name',
            'value' => 'HelpDesk Kemlu',
            'type' => 'string',
            'category' => 'general',
        ]);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'app_name',
            'value' => 'HelpDesk Kemlu',
        ]);
    }

    /** @test */
    public function it_can_retrieve_a_setting_by_key()
    {
        SystemSetting::create([
            'key' => 'max_tickets',
            'value' => '100',
            'type' => 'integer',
            'category' => 'limits',
        ]);

        Cache::flush();

        $value = SystemSetting::get('max_tickets');

        $this->assertEquals('100', $value);
    }

    /** @test */
    public function it_returns_default_value_when_setting_not_found()
    {
        Cache::flush();
        
        $value = SystemSetting::get('non_existent_key', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    /** @test */
    public function it_can_set_a_new_setting()
    {
        Cache::flush();
        
        SystemSetting::set('test_key', 'test_value', 'test_category');

        $this->assertDatabaseHas('system_settings', [
            'key' => 'test_key',
            'value' => 'test_value',
        ]);
    }

    /** @test */
    public function it_can_detect_and_cast_boolean_values()
    {
        Cache::flush();
        
        SystemSetting::set('feature_enabled', true, 'features');

        $setting = SystemSetting::where('key', 'feature_enabled')->first();
        
        $this->assertTrue($setting->getCastedValue());
        $this->assertEquals('boolean', $setting->type);
    }

    /** @test */
    public function it_can_detect_and_cast_integer_values()
    {
        Cache::flush();
        
        SystemSetting::set('max_users', 500, 'limits');

        $setting = SystemSetting::where('key', 'max_users')->first();
        
        $this->assertEquals(500, $setting->getCastedValue());
        $this->assertEquals('integer', $setting->type);
    }

    /** @test */
    public function it_can_detect_and_cast_json_values()
    {
        Cache::flush();
        
        $data = ['items' => ['a', 'b', 'c']];
        SystemSetting::set('json_data', $data, 'data');

        $setting = SystemSetting::where('key', 'json_data')->first();
        
        $this->assertEquals($data, $setting->getCastedValue());
        $this->assertEquals('json', $setting->type);
    }

    /** @test */
    public function it_can_retrieve_settings_by_category()
    {
        Cache::flush();
        
        SystemSetting::create([
            'key' => 'setting1',
            'value' => 'value1',
            'type' => 'string',
            'category' => 'test_category',
        ]);
        SystemSetting::create([
            'key' => 'setting2',
            'value' => 'value2',
            'type' => 'string',
            'category' => 'test_category',
        ]);

        $settings = SystemSetting::getByCategory('test_category');

        $this->assertCount(2, $settings);
        $this->assertArrayHasKey('setting1', $settings);
        $this->assertArrayHasKey('setting2', $settings);
    }

    /** @test */
    public function it_can_retrieve_public_settings()
    {
        Cache::flush();
        
        SystemSetting::create([
            'key' => 'public_setting',
            'value' => 'public_value',
            'type' => 'string',
            'category' => 'general',
            'is_public' => true,
        ]);
        SystemSetting::create([
            'key' => 'private_setting',
            'value' => 'private_value',
            'type' => 'string',
            'category' => 'general',
            'is_public' => false,
        ]);

        $settings = SystemSetting::getPublic();

        $this->assertArrayHasKey('public_setting', $settings);
        $this->assertArrayNotHasKey('private_setting', $settings);
    }

    /** @test */
    public function it_clears_cache_when_setting_is_updated()
    {
        $setting = SystemSetting::create([
            'key' => 'test_setting',
            'value' => 'original_value',
            'type' => 'string',
            'category' => 'test',
        ]);

        Cache::put("system_setting_test_setting", 'original_value', 300);

        $setting->update(['value' => 'updated_value']);

        $cached = Cache::has("system_setting_test_setting");

        $this->assertFalse($cached);
    }

    /** @test */
    public function it_clears_cache_when_setting_is_deleted()
    {
        $setting = SystemSetting::create([
            'key' => 'test_setting',
            'value' => 'test_value',
            'type' => 'string',
            'category' => 'test',
        ]);

        Cache::put("system_setting_test_setting", 'test_value', 300);

        $setting->delete();

        $cached = Cache::has("system_setting_test_setting");

        $this->assertFalse($cached);
    }

    /** @test */
    public function it_can_filter_by_category_scope()
    {
        SystemSetting::create([
            'key' => 'setting1',
            'value' => 'value1',
            'type' => 'string',
            'category' => 'general',
        ]);
        SystemSetting::create([
            'key' => 'setting2',
            'value' => 'value2',
            'type' => 'string',
            'category' => 'security',
        ]);

        $generalSettings = SystemSetting::category('general')->get();

        $this->assertEquals(1, $generalSettings->count());
    }

    /** @test */
    public function it_can_filter_by_public_scope()
    {
        SystemSetting::create([
            'key' => 'public1',
            'value' => 'value1',
            'type' => 'string',
            'category' => 'general',
            'is_public' => true,
        ]);
        SystemSetting::create([
            'key' => 'private1',
            'value' => 'value2',
            'type' => 'string',
            'category' => 'general',
            'is_public' => false,
        ]);

        $publicSettings = SystemSetting::public()->get();

        $this->assertEquals(1, $publicSettings->count());
        $this->assertEquals('public1', $publicSettings->first()->key);
    }

    /** @test */
    public function it_casts_string_1_to_true_for_boolean_type()
    {
        $setting = SystemSetting::create([
            'key' => 'bool_test',
            'value' => '1',
            'type' => 'boolean',
            'category' => 'test',
        ]);

        $this->assertTrue($setting->getCastedValue());
    }

    /** @test */
    public function it_casts_string_0_to_false_for_boolean_type()
    {
        $setting = SystemSetting::create([
            'key' => 'bool_test',
            'value' => '0',
            'type' => 'boolean',
            'category' => 'test',
        ]);

        $this->assertFalse($setting->getCastedValue());
    }
}
