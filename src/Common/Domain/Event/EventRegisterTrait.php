<?php

declare(strict_types=1);

namespace Common\Domain\Event;

trait EventRegisterTrait
{
    /**
     * @var EventDomainInterface[]
     */
    private array $eventsRegistered = [];

    protected function eventDispatchRegister(EventDomainInterface $event): void
    {
        $this->eventsRegistered[] = $event;
    }

    public function getEventsRegistered(): array
    {
        return $this->eventsRegistered;
    }
}
