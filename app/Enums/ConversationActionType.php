<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static UPDATE_NAME()
 * @method static static UPDATE_ICON()
 * @method static static ADD_USER()
 * @method static static REMOVE_USER()
 */

final class ConversationActionType extends Enum
{
    const UPDATE_NAME = 'update_name';
    const UPDATE_ICON = 'update_icon';
    const ADD_USER = 'add_user';
    const REMOVE_USER = 'remove_user';
}
