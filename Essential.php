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

    /**
     * 输出小龙的关于信息
     *
     * @return string
     */
    #[CQCommand('关于小龙')]
    public function cmdAboutMe(): string
    {
        $template = <<<EOF
小龙同学 %s
开发者：%s
开发语言：%s

致谢：%s（排名不分先后）

Copyright © 2017-%s %s. All rights reserved.
EOF;
        $thanks = [
            'Zhamao Framework', 'Go CQHTTP', 'Swoole', '青云客智能聊天', '魅族天气', '结巴分词', 'Jetbrains', 'Docker',
        ];
        sort($thanks);
        return sprintf($template,
            ApplicationVersion::getShortVersion(),
            '夕阳（2496419818）',
            'PHP',
            implode('、', $thanks),
            date('Y'),
            'LoongWork'
        );
    }
}