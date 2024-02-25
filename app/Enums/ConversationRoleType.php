<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static ADMIN()
 * @method static static MODERATOR()
 * @method static static PARTICIPANT()
 */
final class ConversationRoleType extends Enum
{
    const ADMIN = 'admin';
    const MODERATOR = 'moderator';
    const PARTICIPANT = 'participant';
}
