<?php

declare(strict_types=1);

namespace App\Group\Domain\Model;

enum GROUP_TYPE: string
{
    case USER = 'TYPE_USER';
    case GROUP = 'TYPE_GROUP';
}
