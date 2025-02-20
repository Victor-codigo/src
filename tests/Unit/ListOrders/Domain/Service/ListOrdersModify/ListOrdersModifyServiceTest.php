<?php

declare(strict_types=1);

namespace Test\Unit\ListOrders\Domain\Service\ListOrdersModify;

use Common\Domain\Database\Orm\Doctrine\Repository\Exception\DBConnectionException;
use Common\Domain\Database\Orm\Doctrine\Repository\Exception\DBNotFoundException;
use Common\Domain\Model\ValueObject\ValueObjectFactory;
use Common\Domain\Ports\Paginator\PaginatorInterface;
use ListOrders\Domain\Model\ListOrders;
use ListOrders\Domain\Ports\ListOrdersRepositoryInterface;
use ListOrders\Domain\Service\ListOrdersModify\Dto\ListOrdersModifyDto;
use ListOrders\Domain\Service\ListOrdersModify\Exception\ListOrdersModifyNameAlreadyExistsInGroupException;
use ListOrders\Domain\Service\ListOrdersModify\ListOrdersModifyService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ListOrdersModifyServiceTest extends TestCase
{
    private const string GROUP_ID = '4b513296-14ac-4fb1-a574-05bc9b1dbe3f';

    private ListOrdersModifyService $object;
    private MockObject&ListOrdersRepositoryInterface $listOrdersRepository;
    /**
     * @var MockObject&PaginatorInterface<int, ListOrders>
     */
    private MockObject&PaginatorInterface $paginator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->listOrdersRepository = $this->createMock(ListOrdersRepositoryInterface::class);
        $this->paginator = $this->createMock(PaginatorInterface::class);
        $this->object = new ListOrdersModifyService($this->listOrdersRepository);
    }

    private function getListOrders(): ListOrders
    {
        return ListOrders::fromPrimitives(
            'ba6bed75-4c6e-4ac3-8787-5bded95dac8d',
            self::GROUP_ID,
            '2606508b-4516-45d6-93a6-c7cb416b7f3f',
            'list orders name',
            'list orders description',
            new \DateTime()
        );
    }

    private function getListOrders2(): ListOrders
    {
        return ListOrders::fromPrimitives(
            '64a2b2df-165b-4e29-b34a-c9732ee6ccde',
            self::GROUP_ID,
            '2606508b-4516-45d6-93a6-c7cb416b7f3f',
            'list orders name 2',
            'list orders description 2',
            new \DateTime()
        );
    }

    /**
     * @param ListOrders[] $listsOrdersExpected
     * @param ListOrders[] $listsOrdersActual
     */
    private function assertListOrdersIsOk(array $listsOrdersExpected, array $listsOrdersActual): void
    {
        foreach ($listsOrdersExpected as $key => $listOrdersExpected) {
            $this->assertEquals($listOrdersExpected->getId(), $listsOrdersActual[$key]->getId());
            $this->assertEquals($listOrdersExpected->getGroupId(), $listsOrdersActual[$key]->getGroupId());
            $this->assertEquals($listOrdersExpected->getUserId(), $listsOrdersActual[$key]->getUserId());
            $this->assertEquals($listOrdersExpected->getName(), $listsOrdersActual[$key]->getName());
            $this->assertEquals($listOrdersExpected->getDescription(), $listsOrdersActual[$key]->getDescription());
            $this->assertEquals($listOrdersExpected->getDateToBuy(), $listsOrdersActual[$key]->getDateToBuy());
        }
    }

    #[Test]
    public function itShouldModifyTheListOrder(): void
    {
        $listOrder = $this->getListOrders();
        $input = new ListOrdersModifyDto(
            ValueObjectFactory::createIdentifier('8a24edd8-b8e0-4609-b1e6-a67c6c122d61'),
            ValueObjectFactory::createIdentifier('8a24edd8-b8e0-4609-b1e6-a67c6c122d61'),
            ValueObjectFactory::createIdentifier($listOrder->getId()->getValue()),
            ValueObjectFactory::createNameWithSpaces('list orders name modified'),
            ValueObjectFactory::createDescription('list orders description modified'),
            ValueObjectFactory::createDateNowToFuture(new \DateTime())
        );
        $listOrderExpected = new ListOrders(
            $listOrder->getId(),
            $listOrder->getGroupId(),
            $input->userId,
            $input->name,
            $input->description,
            $input->dateToBuy
        );

        $this->listOrdersRepository
            ->expects($this->once())
            ->method('findListOrdersByNameOrFail')
            ->with($input->name, $input->groupId)
            ->willReturn($listOrder);

        $this->listOrdersRepository
            ->expects($this->once())
            ->method('findListOrderByIdOrFail')
            ->with([$input->listOrdersId], $input->groupId)
            ->willReturn($this->paginator);

        $this->listOrdersRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (array $listsOrdersActual) use ($listOrderExpected): true {
                $this->assertListOrdersIsOk([$listOrderExpected], $listsOrdersActual);

                return true;
            }));

        $this->paginator
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$listOrder]));

        $return = $this->object->__invoke($input);

        $this->assertListOrdersIsOk([$listOrderExpected], [$return]);
    }

    #[Test]
    public function itShouldModifyTheListOrderNameIsInAnotherGroup(): void
    {
        $listOrder = $this->getListOrders();
        $input = new ListOrdersModifyDto(
            ValueObjectFactory::createIdentifier('8a24edd8-b8e0-4609-b1e6-a67c6c122d61'),
            ValueObjectFactory::createIdentifier('8a24edd8-b8e0-4609-b1e6-a67c6c122d61'),
            ValueObjectFactory::createIdentifier($listOrder->getId()->getValue()),
            ValueObjectFactory::createNameWithSpaces('List order name 1'),
            ValueObjectFactory::createDescription('list orders description modified'),
            ValueObjectFactory::createDateNowToFuture(new \DateTime())
        );
        $listOrderExpected = new ListOrders(
            $listOrder->getId(),
            $listOrder->getGroupId(),
            $input->userId,
            $input->name,
            $input->description,
            $input->dateToBuy
        );

        $this->listOrdersRepository
            ->expects($this->once())
            ->method('findListOrdersByNameOrFail')
            ->with($input->name, $input->groupId)
            ->willThrowException(new DBNotFoundException());

        $this->listOrdersRepository
            ->expects($this->once())
            ->method('findListOrderByIdOrFail')
            ->with([$input->listOrdersId], $input->groupId)
            ->willReturn($this->paginator);

        $this->listOrdersRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (array $listsOrdersActual) use ($listOrderExpected): true {
                $this->assertListOrdersIsOk([$listOrderExpected], $listsOrdersActual);

                return true;
            }));

        $this->paginator
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$listOrder]));

        $return = $this->object->__invoke($input);

        $this->assertListOrdersIsOk([$listOrderExpected], [$return]);
    }

    #[Test]
    public function itShouldFailModifyingTheListOrderNameIsAlreadyInTheInTheGroup(): void
    {
        $listOrder = $this->getListOrders();
        $listOrder2 = $this->getListOrders2();
        $input = new ListOrdersModifyDto(
            ValueObjectFactory::createIdentifier('8a24edd8-b8e0-4609-b1e6-a67c6c122d61'),
            ValueObjectFactory::createIdentifier('8a24edd8-b8e0-4609-b1e6-a67c6c122d61'),
            ValueObjectFactory::createIdentifier($listOrder->getId()->getValue()),
            ValueObjectFactory::createNameWithSpaces('List order name 1'),
            ValueObjectFactory::createDescription('list orders description modified'),
            ValueObjectFactory::createDateNowToFuture(new \DateTime())
        );

        $this->listOrdersRepository
            ->expects($this->once())
            ->method('findListOrdersByNameOrFail')
            ->with($input->name, $input->groupId)
            ->willReturn($listOrder2);

        $this->listOrdersRepository
            ->expects($this->never())
            ->method('findListOrderByIdOrFail');

        $this->listOrdersRepository
            ->expects($this->never())
            ->method('save');

        $this->paginator
            ->expects($this->never())
            ->method('getIterator');

        $this->expectException(ListOrdersModifyNameAlreadyExistsInGroupException::class);
        $this->object->__invoke($input);
    }

    #[Test]
    public function itShouldFailModifyingTheListOrderListOrderNotFound(): void
    {
        $listOrder = $this->getListOrders();
        $input = new ListOrdersModifyDto(
            ValueObjectFactory::createIdentifier('8a24edd8-b8e0-4609-b1e6-a67c6c122d61'),
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            ValueObjectFactory::createIdentifier($listOrder->getId()->getValue()),
            ValueObjectFactory::createNameWithSpaces('list orders name modified'),
            ValueObjectFactory::createDescription('list orders description modified'),
            ValueObjectFactory::createDateNowToFuture(new \DateTime())
        );

        $this->listOrdersRepository
            ->expects($this->once())
            ->method('findListOrdersByNameOrFail')
            ->with($input->name, $input->groupId)
            ->willThrowException(new DBNotFoundException());

        $this->listOrdersRepository
            ->expects($this->once())
            ->method('findListOrderByIdOrFail')
            ->with([$input->listOrdersId], $input->groupId)
            ->willThrowException(new DBNotFoundException());

        $this->listOrdersRepository
            ->expects($this->never())
            ->method('save');

        $this->paginator
            ->expects($this->never())
            ->method('getIterator');

        $this->expectException(DBNotFoundException::class);
        $this->object->__invoke($input);
    }

    #[Test]
    public function itShouldFailModifyingTheListOrderSaveError(): void
    {
        $listOrder = $this->getListOrders();
        $input = new ListOrdersModifyDto(
            ValueObjectFactory::createIdentifier('8a24edd8-b8e0-4609-b1e6-a67c6c122d61'),
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            ValueObjectFactory::createIdentifier($listOrder->getId()->getValue()),
            ValueObjectFactory::createNameWithSpaces('list orders name modified'),
            ValueObjectFactory::createDescription('list orders description modified'),
            ValueObjectFactory::createDateNowToFuture(new \DateTime())
        );

        $this->listOrdersRepository
            ->expects($this->once())
            ->method('findListOrdersByNameOrFail')
            ->with($input->name, $input->groupId)
            ->willThrowException(new DBNotFoundException());

        $this->listOrdersRepository
            ->expects($this->once())
            ->method('findListOrderByIdOrFail')
            ->with([$input->listOrdersId], $input->groupId)
            ->willReturn($this->paginator);

        $this->listOrdersRepository
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new DBConnectionException());

        $this->paginator
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$listOrder]));

        $this->expectException(DBConnectionException::class);
        $this->object->__invoke($input);
    }
}
