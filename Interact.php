<?php

namespace Bot\Module\Essential;

use Bot\Module\Essential\Entity\User;
use ZM\API\CQ;

class Interact
{
    public function success($msg): void
    {
        must_ctx()->reply($msg);
//        must_ctx()->reply(EmojiHelper::getInstance()->addEmoji($msg, 'happy'));
    }

    public function fail($msg): void
    {
        must_ctx()->reply($msg);
//        must_ctx()->reply(EmojiHelper::getInstance()->addEmoji($msg, 'sad'));
    }

    /**
     * 要求命令调用者提供一个用户
     *
     * @param string $prompt 提示信息
     * @param int $retry_times 重试次数
     * @return User 用户ID
     */
    public function askForUser(string $prompt, int $retry_times = 3): User
    {
        $result = must_ctx()->getNextArg($prompt);
        $decoded = CQ::getCQ($result);
        if (!$decoded || $decoded['type'] !== 'at') {
            if ($retry_times > 0) {
                return $this->askForUser($prompt, $retry_times - 1);
            }
            $this->fail('这不是一个有效的用户！命令取消！');
            throw new \InvalidArgumentException('用户没有提供有效的用户');
        }
        $id = $decoded['params']['qq'];
        $user = must_ctx()->getRobot()->getGroupMemberInfo(
            must_ctx()->getGroupId(),
            $id,
        )['data'];
        return new User($id, $user['nickname'], $user['card']);
    }
}