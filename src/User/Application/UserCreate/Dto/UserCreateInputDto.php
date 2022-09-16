<?php

declare(strict_types=1);

namespace User\Application\UserCreate\Dto;

use Common\Domain\Model\ValueObject\array\Roles;
use Common\Domain\Model\ValueObject\String\Email;
use Common\Domain\Model\ValueObject\String\Name;
use Common\Domain\Model\ValueObject\String\Password;
use Common\Domain\Model\ValueObject\ValueObjectFactory;
use Common\Domain\Service\ServiceInputDtoInterface;
use Common\Domain\Validation\ValidationInterface;

final class UserCreateInputDto implements ServiceInputDtoInterface
{
    public readonly Email|null $email;
    public readonly Password|null $password;
    public readonly Name|null $name;
    public readonly Roles|null $roles;
    public readonly ProfileCreateInputDto|null $profile;

    private function __construct(string|null $email, string|null $password, string|null $name, Roles|null $roles, ProfileCreateInputDto|null $profile)
    {
        $this->email = ValueObjectFactory::createEmail($email);
        $this->password = valueObjectFactory::createPassword($password);
        $this->name = valueObjectFactory::createName($name);
        $this->roles = $roles;
        $this->profile = $profile;
    }

    public static function create(string|null $email, string|null $password, string|null $name, Roles|null $roles): self
    {
        $profile = ProfileCreateInputDto::create(null);

        return new self($email, $password, $name, $roles, $profile);
    }

    public static function createWithProfile(string|null $email, string|null $password, string|null $name, Roles|null $roles, string|null $image): self
    {
        $profile = ProfileCreateInputDto::create($image);

        return new self($email, $password, $name, $roles, $profile);
    }

    public function validate(ValidationInterface $validator): array
    {
        return $validator->validateValueObjectArray([
            $this->email,
            $this->password,
            $this->name,
            $this->roles,
        ]);
    }
}
