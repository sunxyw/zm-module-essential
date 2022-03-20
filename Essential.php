<?php

namespace Bot\Module\Essential;

use ZM\Annotation\CQ\CQCommand;

class Essential extends Base
{
    #[CQCommand('版本')]
    public function cmdGetAppVersion(): string
    {
        return ApplicationVersion::getFullVersion();
    }
}