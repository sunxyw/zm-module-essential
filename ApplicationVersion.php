<?php

namespace Bot\Module\Essential;

use Composer\Autoload\ClassLoader;
use Jelix\Version\Parser;
use Jelix\Version\Version;
use ZM\API\GoCqhttpAPIV11;
use ZM\API\ZMRobot;
use ZM\Config\ZMConfig;
use ZM\ConsoleApplication;
use ZM\Exception\RobotNotFoundException;

final class ApplicationVersion
{
    public static function getVersion(): Version
    {
        $reflection = new \ReflectionClass(ClassLoader::class);
        $root_path = dirname($reflection->getFileName(), 3);
        try {
            $composer_json = json_decode(file_get_contents($root_path . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
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
        if (!ZMConfig::get('secrets', 'update.enable')) {
            return self::getVersion();
        }
        $context = stream_context_create([
            'http' => [
                'header' => 'Authorization: Basic ' . ZMConfig::get('secrets', 'update.token'),
            ],
        ]);
        $json = file_get_contents('https://api.github.com/repos/loongwork/xiaoloong/releases/latest', false, $context);
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $version = Parser::parse($data['tag_name']);
        } catch (\JsonException|\Exception) {
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