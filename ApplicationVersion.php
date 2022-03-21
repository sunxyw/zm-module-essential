<?php

namespace Bot\Module\Essential;

use Composer\Autoload\ClassLoader;
use Jelix\Version\Parser;
use Jelix\Version\Version;
use ZM\API\ZMRobot;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\ConsoleApplication;
use ZM\Exception\RobotNotFoundException;
use ZM\Exception\ZMException;
use ZM\Store\LightCache;

final class ApplicationVersion
{
    public static function getVersion(): Version
    {
        $reflection = new \ReflectionClass(ClassLoader::class);
        $root_path = dirname($reflection->getFileName(), 3);
        try {
            if (LightCache::isset('composer_version')) {
                $composer_json = [
                    'version' => LightCache::get('composer_version'),
                ];
            } else {
                try {
                    $composer_json = json_decode(file_get_contents($root_path . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    $composer_json = [
                        'version' => '0.0.0-unknown',
                    ];
                }
                LightCache::set('composer_version', $composer_json['version']);
            }
        } catch (ZMException $e) {
            $composer_json = [
                'version' => '0.0.0-unknown',
            ];
        }

        try {
            $version = Parser::parse($composer_json['version']);
        } catch (\Exception) {
            $version = new Version([0, 0, 0], ['unknown']);
        }

        return $version;
    }

    public static function getShortVersion(): string
    {
        return self::getVersion()->toString();
    }

    public static function getLatestVersion(): Version
    {
        $update = ZMConfig::get('secrets', 'update');
        if (!$update || !$update['enable']) {
            Console::warning('Update is disabled.');
            return self::getVersion();
        }
        Console::verbose('Checking for update...');
        Console::verbose('Using token: ' . $update['token']);
        $context = stream_context_create([
            'http' => [
                'header' => [
                    'Authorization: Basic ' . $update['token'],
                    'User-Agent: PHP',
                ],
            ],
        ]);
        $json = file_get_contents('https://api.github.com/repos/loongwork/xiaoloong/releases/latest', false, $context);
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $tag = $data['tag_name'];
            preg_match('/(\d+)\.(\d+)(?:\.(\d+))?(?:-(\w+))?/', $tag, $matches);
            $version = Parser::parse($matches[0]);
        } catch (\JsonException|\Exception $e) {
            Console::warning('Failed to get latest version: ' . $e->getMessage());
            $version = new Version([0, 0, 0], ['unknown']);
        }
        return $version;
    }

    public static function getLongVersion(): string
    {
        $version = self::getVersion();

        $commit_hash = trim(exec('git log --pretty="%h" -n1 HEAD'));

        try {
            $commit_date = new \DateTime(trim(exec('git log -n1 --pretty=%ci HEAD')));
        } catch (\Exception) {
            $commit_date = new \DateTime('2496-08-11');
        }
        $commit_date->setTimezone(new \DateTimeZone('Asia/Shanghai'));

        return sprintf(
            '%s.%s (%s)',
            self::getShortVersion(),
            $commit_hash,
            $commit_date->format('Y-m-d H:i:s')
        );
    }

    public static function getProtocolVersion(): string
    {
        try {
            $version = ZMRobot::getRandom()->getVersionInfo()['data'];
            return sprintf('Onebot %s (%s %s)', $version['protocol_version'], $version['app_name'], $version['app_version']);
        } catch (RobotNotFoundException) {
            $version = new Version([0, 0, 0], ['unknown']);
            return $version->toString();
        }
    }

    public static function getFullVersion(): string
    {
        $template = <<<EOF
最新版本：%s
当前版本：%s
上游版本：%s
协议版本：%s
EOF;
        return sprintf($template, self::getLatestVersion()->toString(), self::getLongVersion(), ConsoleApplication::VERSION, self::getProtocolVersion());
    }
}