<?php

namespace Bot\Module\Essential;

use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\Pure;
use ZM\API\CQ;
use ZM\API\ZMRobot;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Context\ContextInterface;
use ZM\Event\EventTracer;
use ZM\Utils\DataProvider;

/**
 * Class Base.
 *
 * @property-read ZMRobot $bot
 */
class Base
{
    protected Interact $interact;
    protected Language $lang;

    public function __construct()
    {
        $this->interact = new Interact();
        $this->lang = new Language(DataProvider::getSourceRootDir() . '/langs');
    }

    public function __get(string $name)
    {
        if ($name === 'bot') {
            return $this->getContext()->getRobot();
        }
        return $this->$name;
    }

    #[NoReturn]
    public function __set(string $name, $value): void
    {
        $this->$name = $value;
    }

    public function __isset(string $name): bool
    {
        return $name === 'bot';
    }

    protected function getContext(): ContextInterface
    {
        return ctx();
    }

    protected function getRealm(): string
    {
        if (isset($this->getContext()->getData()['override_realm'])) {
            return $this->getContext()->getData()['override_realm'];
        }

        $realm = ZMConfig::get('realms');

        $gid = $this->getContext()->getGroupId();
        foreach ($realm as $key => $value) {
            if (in_array($gid, $value, true)) {
                return $key;
            }
        }
        throw new \RuntimeException('Realm not found: ' . $gid);
    }

    protected function trans(string $key, ...$args): string
    {
        $caller = EventTracer::getCurrentEvent();
        if (is_null($caller)) {
            $backtrace = debug_backtrace();
            $caller = reset($backtrace);
            $caller = $caller['object'];
            $reflection = new \ReflectionObject($caller);
            $namespace = explode('\\', $reflection->getNamespaceName());
        } else {
            $caller = $caller->class;
            $namespace = explode('\\', $caller);
            array_pop($namespace);
        }

        $module = strtolower(end($namespace));
        Console::verbose("Translate $key in $module");
        $str = $this->lang->get("modules.$module.messages.$key", ...$args);
//        $this->appendRandom($str);
        return $str;
    }

    protected function appendRandom(string &$string, int $length = 5): void
    {
        $string .= "\n------------------------------\n";
        for ($i = 0; $i < $length; $i++) {
            try {
                $string .= CQ::face(random_int(0, 274));
            } catch (\Exception $e) {
                $string .= ' ';
            }
        }
    }
}