<?php

declare(strict_types=1);

namespace Order\Application\OrderModify\Dto;

use Common\Domain\Application\ApplicationOutputInterface;
use Order\Domain\Model\Order;

class OrderModifyOutputDto implements ApplicationOutputInterface
{
    public function __construct(
        public readonly Order $order
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->order->getId()->getValue(),
        ];
    }
}
