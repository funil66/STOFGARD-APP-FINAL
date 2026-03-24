<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{

    protected $fillable = ['key', 'value', 'group', 'type'];

    public static function get($key, $default = null)
    {
        try {
            if (!Schema::hasTable((new self())->getTable())) {
                return $default;
            }

            $setting = self::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            if ($setting->type === 'boolean') {
                return filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
            }

            if ($setting->type === 'json') {
                return json_decode($setting->value, true);
            }

            return $setting->value;
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function set($key, $value, $group = 'geral', $type = 'string')
    {
        try {
            if (!Schema::hasTable((new self())->getTable())) {
                return null;
            }

            $payload = ['value' => $type === 'json' ? json_encode($value) : $value, 'group' => $group, 'type' => $type];

            return self::updateOrCreate(['key' => $key], $payload);
        } catch (\Throwable) {
            return null;
        }
    }
}
