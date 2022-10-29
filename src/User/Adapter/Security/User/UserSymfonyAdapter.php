<?php

declare(strict_types=1);

namespace User\Adapter\Security\User;

use Common\Domain\Model\ValueObject\Object\Rol;
use Common\Domain\Model\ValueObject\ValueObjectFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;
use User\Domain\Model\User;
use User\Domain\Port\PasswordHasher\PasswordHasherInterface;
use User\Domain\Port\User\UserInterface;

class UserSymfonyAdapter implements SymfonyUserInterface, PasswordAuthenticatedUserInterface, PasswordHasherInterface, UserInterface
{
    private User $user;
    private UserPasswordHasherInterface $passwordHasher;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,

        #[Autowire(expression: 'null')]
        User|null $user = null
    ) {
        $this->passwordHasher = $passwordHasher;
        $this->user = $user ?? $this->createUserNull();
    }

    public function getRoles(): array
    {
        $roles = $this->user
            ->getRoles()
            ->getValue();

        return array_map(
            fn (Rol $rol) => $rol->getValue()->value,
            $roles
        );
    }

    public function eraseCredentials()
    {
        $this->user->setPassword(ValueObjectFactory::createPassword(null));
    }

    public function getUserIdentifier(): string
    {
        return $this->user
            ->getEmail()
            ->getValue();
    }

    public function getPassword(): string
    {
        return $this->user
            ->getPassword()
            ->getValue();
    }

    public function passwordHash(string $plainPassword): void
    {
        $this->user->setPassword(ValueObjectFactory::createPassword(
            $this->passwordHasher->hashPassword($this, $plainPassword)
        ));
    }

    public function passwordIsValid(string $plainPassword): bool
    {
        if (!$this->passwordHasher->isPasswordValid($this, $plainPassword)) {
            return false;
        }

        if ($this->passwordNeedsRehash()) {
            $this->passwordHash($plainPassword);
        }

        return true;
    }

    public function passwordNeedsRehash(): bool
    {
        return $this->passwordHasher->needsRehash($this);
    }

    private function createUserNull(): User
    {
        return new User(
            ValueObjectFactory::createIdentifier(null),
            ValueObjectFactory::createEmail(null),
            ValueObjectFactory::createPassword(null),
            ValueObjectFactory::createName(null),
            ValueObjectFactory::createRoles(null),
            ValueObjectFactory::createIdentifier(null),
        );
    }
}
