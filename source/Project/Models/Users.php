<?php

namespace Source\Project\Models;

use Source\Base\Model\ModelPdo;

/**
 * @property int|null $is_banned
 * @property mixed|string[] $markets
 */
class Users extends ModelPdo
{
    /**
     * @var int
     */
    public int $id;
    /**
     * @var string
     */
    public string|null $token;
    /**
     * @var int
     */
    public string $created_at;
    /**
     * @var string|null
     */
    public static ?string $static_table = 'users';
}

