<?php

namespace App;

class Lang
{
    protected $locale;
    protected $path;

    public function __construct($locale = 'ro', $path =  __DIR__ . '/../lang/')
    {
        $this->locale = $locale;
        $this->path = rtrim($path, '/') . '/';
    }

    /**
     * Отримати переклад за ключем.
     *
     * @param string $key
     * @param array $replace
     * @return string
     */
    public function get($key, $replace = [])
    {
        $segments = explode('.', $key);
        $file = $this->path . $this->locale . '/' . $segments[0] . '.php';

        if (!file_exists($file)) {
            return $key;
        }

        $translations = include $file;
        $translation = $translations[$segments[1]] ?? $key;

        foreach ($replace as $search => $value) {
            $translation = str_replace(":$search", $value, $translation);
        }

        return $translation;
    }

    /**
     * Встановити поточну локаль.
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }
}
