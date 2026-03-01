<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{

    protected $fillable = ['key', 'value', 'group', 'type'];

    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if (!$setting)
            return $default;

        // Cast common types
        if ($setting->type === 'boolean') {
            return filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
        }
        if ($setting->type === 'json') {
            return json_decode($setting->value, true);
        }

        return $setting->value;
    }

    public static function set($key, $value, $group = 'geral', $type = 'string')
    {
        $payload = ['value' => $type === 'json' ? json_encode($value) : $value, 'group' => $group, 'type' => $type];
        return self::updateOrCreate(['key' => $key], $payload);
    }
}
