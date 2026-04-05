<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $guarded = [];

    public static function value(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        if ($setting === null) {
            return $default;
        }

        return static::castValue($setting->value, $default);
    }

    public static function boolean(string $key, bool $default = false): bool
    {
        return (bool) static::value($key, $default);
    }

    public static function featureFlags(): array
    {
        $defaults = [
            'enable_credit_sales' => true,
            'enable_etims' => false,
            'enable_loyalty_points' => true,
            'enable_hardware_printer' => false,
            'enable_fractional_stock' => false,
            'enable_wholesale' => false,
            'enable_sales_hours_lock' => false,
            'is_app_configured' => false,
        ];

        $settings = static::query()
            ->whereIn('key', array_keys($defaults))
            ->pluck('value', 'key')
            ->all();

        return collect($defaults)->mapWithKeys(
            fn (bool $default, string $key): array => [$key => (bool) static::castValue($settings[$key] ?? null, $default)]
        )->all();
    }

    private static function castValue(mixed $value, mixed $default = null): mixed
    {
        if ($value === null) {
            return $default;
        }

        if (is_bool($default)) {
            return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
        }

        return $value;
    }
}
