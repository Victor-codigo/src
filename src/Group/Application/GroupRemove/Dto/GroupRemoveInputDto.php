<?php

declare(strict_types=1);

namespace Group\Application\GroupRemove\Dto;

use Common\Domain\Model\ValueObject\String\Identifier;
use Common\Domain\Model\ValueObject\ValueObjectFactory;
use Common\Domain\Security\UserShared;
use Common\Domain\Service\ServiceInputDtoInterface;
use Common\Domain\Validation\Common\VALIDATION_ERRORS;
use Common\Domain\Validation\ValidationInterface;

class GroupRemoveInputDto implements ServiceInputDtoInterface
{
    /**
     * @var Identifier[]
     */
    public readonly array $groupsId;
    public readonly UserShared $userSession;

    /**
     * @param string[]|null $groupsId
     */
    public function __construct(UserShared $userSession, ?array $groupsId)
    {
        $this->userSession = $userSession;
        $this->groupsId = array_map(
            fn (string $groupsId): Identifier => ValueObjectFactory::createIdentifier($groupsId),
            $groupsId ?? []
        );
    }

    /**
     * @return array{}|array<int|string, VALIDATION_ERRORS[]>
     */
    #[\Override]
    public function validate(ValidationInterface $validator): array
    {
        $errorListGroupsIdEmpty = $validator
            ->setValue($this->groupsId)
            ->notBlank()
            ->validate();

        if (!empty($errorListGroupsIdEmpty)) {
            $errorListGroupsIdEmpty = ['groups_id_empty' => $errorListGroupsIdEmpty];
        }

        $errorListGroupsId = $validator->validateValueObjectArray($this->groupsId);

        if (!empty($errorListGroupsId)) {
            $errorListGroupsId = ['groups_id' => $errorListGroupsId];
        }

        return [...$errorListGroupsIdEmpty, ...$errorListGroupsId];
    }
}
