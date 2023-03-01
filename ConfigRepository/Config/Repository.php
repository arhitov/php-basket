<?php

namespace App\Config;

use ArrayAccess;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Support\Arr;

class Repository extends ConfigRepository implements ArrayAccess
{

    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        $name = explode('.', $key)[0];
        if (! array_key_exists($name, $this->items)) {
            $this->_load($name);
        }
        return Arr::has($this->items, $key);
    }

    /**
     * Get the specified configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        $result = Arr::get($this->items, $key, 'key-not-found');

        if ('key-not-found' !== $result) {
            return $result;
        }

        $name = explode('.', $key)[0];
        $this->_load($name);

        return Arr::get($this->items, $key, $default);
    }

    /**
     * Get many configuration values.
     *
     * @param  array  $keys
     * @return array
     */
    public function getMany($keys)
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }

            $config[$key] = $this->get($key, $default);
        }

        return $config;
    }

    /**
     * Set a given configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return void
     */
    public function set($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            Arr::set($this->items, $key, $value);
        }
    }

    protected function _load(string $name)
    {
        // clgs 2023.02.17 23:47 предполагаем, что файл существует.
        // Если его нет, то программист допустил ошибку.
        // Пользовательские данные сюда не должны передаваться
        $this->items[$name] = require app_config_path($name . '.php');
    }
}