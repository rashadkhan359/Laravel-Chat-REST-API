<?php

namespace App\Enums;

use BenSampo\Enum\Enum as BaseEnum;


/**
 * @method static static DIRECT()
 * @method static static GROUP()
 */

final class ConversationType extends BaseEnum
{
    const DIRECT = 'direct';
    const GROUP = 'group';
}
