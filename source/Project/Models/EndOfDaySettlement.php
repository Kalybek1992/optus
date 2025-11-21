<?php

namespace Source\Project\Models;

use Source\Base\Model\ModelPdo;

/**
 * @property int|null $is_banned
 * @property mixed|string[] $markets
 */
class EndOfDaySettlement extends ModelPdo
{

    public static ?string $static_table = 'end_of_day_settlement';
}
