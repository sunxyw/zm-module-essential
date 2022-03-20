<?php

namespace Bot\Module\Essential\Entity;

use ZM\API\CQ;

class User
{
    public int $id;

    public string $username;

    public string $nickname;

    /**
     * User constructor.
     *
     * @param int $id
     * @param string $username
     * @param string $nickname
     */
    public function __construct(int $id, string $username, string $nickname)
    {
        $this->id = $id;
        $this->username = $username;
        $this->nickname = $nickname;
    }

    /**
     * Convert this user to a mention string.
     *
     * @return string
     */
    public function toMention(): string
    {
        return CQ::at($this->id);
    }
}