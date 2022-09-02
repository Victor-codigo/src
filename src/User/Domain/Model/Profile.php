<?php

declare(strict_types=1);

namespace User\Domain\Model;

class Profile extends EntityBase
{
    protected string $id;
    protected string|null $image = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function getImage(): string|null
    {
        return $this->image;
    }

    public function setImage($image): self
    {
        $this->image = $image;

        return $this;
    }

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'image' => $this->image,
        ];
    }
}
