<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    public static function get($type)
    {
        if(env('INSTALLATION_STATUS')){
            switch ($type) {
                case 'tax':
                    return Cache::remember('tax', now()->addHours(24), function () {
                        return \App\Models\Tax::all();
                    });
                case 'unit':
                    return Cache::remember('unit', now()->addHours(24), function () {
                        return \App\Models\Unit::all();
                    });
                case 'appSetting':
                    return Cache::remember('appSetting', now()->addHours(24), function () {
                        return \App\Models\AppSettings::first();
                    });
                default:
                    throw new \Exception("Invalid cache type: $type");
            }
        }
    }
}