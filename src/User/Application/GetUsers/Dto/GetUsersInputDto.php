<?php

declare(strict_types=1);

namespace User\Application\GetUsers\Dto;

use Common\Domain\Model\ValueObject\String\Identifier;
use Common\Domain\Model\ValueObject\ValueObjectFactory;
use Common\Domain\Service\ServiceInputDtoInterface;
use Common\Domain\Validation\Common\VALIDATION_ERRORS;
use Common\Domain\Validation\ValidationInterface;
use User\Domain\Model\User;

class GetUsersInputDto implements ServiceInputDtoInterface
{
    /**
     * @var Identifier[]
     */
    public readonly ?array $usersId;
    public readonly User $userSession;

    /**
     * @param string[]|null $usersId
     */
    public function __construct(User $userSession, ?array $usersId)
    {
        $this->userSession = $userSession;

        if (null === $usersId) {
            $this->usersId = null;

            return;
        }

        $this->usersId = array_map(
            fn (string $id): Identifier => ValueObjectFactory::createIdentifier($id),
            $usersId
        );
    }

    /**
     * @return array{}|VALIDATION_ERRORS[]|array<int|string, VALIDATION_ERRORS[]>
     */
    #[\Override]
    public function validate(ValidationInterface $validator): array
    {
        $errorList = $validator
            ->setValue($this->usersId)
            ->notNull()
            ->notBlank()
            ->validate();

        if (!empty($errorList)) {
            return $errorList;
        }

        return $validator->validateValueObjectArray($this->usersId);
    }
}
