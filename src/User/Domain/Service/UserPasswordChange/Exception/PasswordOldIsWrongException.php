<?php

declare(strict_types=1);

namespace User\Domain\Service\UserPasswordChange\Exception;

use Common\Domain\Exception\DomainException;

class PasswordOldIsWrongException extends DomainException
{
    public static function fromMessage(string $message): static
    {
        return new static($message);
    }
}
