<?php

if (!function_exists('admin_resource_route')) {
    /**
     * Try to resolve a named route, fallback to a given URL if not found.
     *
     * @param string $name
     * @param string $fallback
     * @param array $params
     * @return string
     */
    function admin_resource_route($name, $fallback = '', $params = [])
    {
        try {
            if (\Route::has($name)) {
                return route($name, $params);
            }
        } catch (\Throwable $e) {
            // ignore
        }
        // fallback URL (with replacements if needed)
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $fallback = str_replace('{' . $key . '}', $value, $fallback);
            }
        }
        return url($fallback);
    }
}
