<?php

namespace Bot\Module\Essential;

use JetBrains\PhpStorm\Pure;
use Symfony\Component\Yaml\Parser;

class Language
{
    /**
     * Language file dir path.
     * This is the path to the language files.
     *
     * @var string
     */
    private string $dir;

    /**
     * Fallback language.
     * This is the fallback language which is used when no language file is available.
     *
     * @var string
     */
    private string $fallback;

    /**
     * Forced language.
     * This is the forced language which will override the user's language.
     *
     * @var string
     */
    private string $forced = '';

    /**
     * Languages.
     *
     * @var array
     */
    private array $languages = [];

    /**
     * Constructor.
     *
     * @param string $dir
     * @param string $fallback
     */
    public function __construct(string $dir, string $fallback = 'en')
    {
        $this->dir = $dir;
        $this->fallback = $fallback;
        $this->scanLanguages();
    }

    /**
     * Set the forced language.
     *
     * @param string $lang
     * @return void
     */
    public function force(string $lang): void
    {
        $this->forced = $lang;
    }

    /**
     * Get the language.
     *
     * @return string
     */
    public function getLang(): string
    {
        return 'zh';
    }

    /**
     * Get the language string by key.
     *
     * @param string $key
     * @param mixed ...$args
     * @return array|string
     */
    #[Pure]
    public function get(string $key, ...$args): array|string
    {
        $lang = $this->forced;
        if (empty($lang)) {
            $lang = $this->getLang();
        }
        if (empty($lang)) {
            $lang = $this->fallback;
        }
        if (empty($this->languages[$lang])) {
            return $key;
        }
        $data = $this->languages[$lang];
        $keys = explode('.', $key);
        foreach ($keys as $k) {
            if (empty($data[$k])) {
                return $k;
            }
            $data = $data[$k];
        }
        if (empty($args)) {
            return $data;
        }
        return vsprintf($data, $args);
    }

    /**
     * Scan the language files.
     *
     * @return void
     */
    private function scanLanguages(): void
    {
        $this->languages = [];
        $dir = $this->dir;
        $parser = new Parser();
        foreach (new \DirectoryIterator($dir) as $file) {
            if ($file->isDot()) {
                continue;
            }
            $filename = $file->getFilename();
            if (str_ends_with($filename, '.yaml')) {
                $lang = substr($filename, 0, -5);
            } elseif (str_ends_with($filename, '.yml')) {
                $lang = substr($filename, 0, -4);
            } else {
                continue;
            }
            $this->languages[$lang] = $parser->parseFile($file->getPathname());
        }
    }
}