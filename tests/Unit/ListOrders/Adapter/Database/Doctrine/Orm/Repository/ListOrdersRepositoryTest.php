<?php

declare(strict_types=1);

namespace Test\Unit\ListOrders\Adapter\Database\Doctrine\Orm\Repository;

use Common\Domain\Database\Orm\Doctrine\Repository\Exception\DBConnectionException;
use Common\Domain\Database\Orm\Doctrine\Repository\Exception\DBNotFoundException;
use Common\Domain\Database\Orm\Doctrine\Repository\Exception\DBUniqueConstraintException;
use Common\Domain\Model\ValueObject\String\Identifier;
use Common\Domain\Model\ValueObject\ValueObjectFactory;
use Common\Domain\Validation\Filter\FILTER_STRING_COMPARISON;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\Persistence\ObjectManager;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use ListOrders\Adapter\Database\Orm\Doctrine\Repository\ListOrdersRepository;
use ListOrders\Domain\Model\ListOrders;
use Order\Domain\Model\Order;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Product\Adapter\Database\Orm\Doctrine\Repository\ProductRepository;
use Product\Domain\Model\Product;
use Shop\Adapter\Database\Orm\Doctrine\Repository\ShopRepository;
use Shop\Domain\Model\Shop;
use Test\Unit\DataBaseTestCase;

class ListOrdersRepositoryTest extends DataBaseTestCase
{
    use ReloadDatabaseTrait;

    private const string LIST_ORDERS_EXISTS_ID = 'ba6bed75-4c6e-4ac3-8787-5bded95dac8d';
    private const array LIST_ORDERS_ID = [
        'ba6bed75-4c6e-4ac3-8787-5bded95dac8d',
        'd446eab9-5199-48d0-91f5-0407a86bcb4f',
        'f1559a23-2f92-4660-a335-b1052d7395da',
        'f2980f67-4eb9-41ca-b452-ffa2c7da6a37',
    ];
    private const string GROUP_ID = '4b513296-14ac-4fb1-a574-05bc9b1dbe3f';
    private const string GROUP_ID_2 = 'fdb242b4-bac8-4463-88d0-0941bb0beee0';
    private const string GROUP_ID_3 = 'a5002966-dbf7-4f76-a862-23a04b5ca465';
    private const string USER_ID = '2606508b-4516-45d6-93a6-c7cb416b7f3f';

    private ListOrdersRepository $object;
    private ProductRepository $productRepository;
    private ShopRepository $shopRepository;
    private MockObject&ConnectionException $connectionException;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->object = $this->entityManager->getRepository(ListOrders::class);
        $this->productRepository = $this->entityManager->getRepository(Product::class);
        $this->shopRepository = $this->entityManager->getRepository(Shop::class);
        $this->connectionException = $this->createMock(ConnectionException::class);
    }

    /**
     * @return Identifier[]
     */
    private function getListOrdersId(): array
    {
        return array_map(
            fn (string $listOrdersId) => ValueObjectFactory::createIdentifier($listOrdersId),
            self::LIST_ORDERS_ID
        );
    }

    private function getListOrders(): ListOrders
    {
        return ListOrders::fromPrimitives(
            '9a48ac5b-4571-43fd-ac80-28b08124ffb8',
            self::GROUP_ID,
            self::USER_ID,
            'listOrders name',
            'listOrders description',
            new \DateTime()
        );
    }

    private function getListOrdersExists(): ListOrders
    {
        return ListOrders::fromPrimitives(
            self::LIST_ORDERS_EXISTS_ID,
            self::GROUP_ID,
            self::USER_ID,
            'listOrders name',
            'listOrders description',
            new \DateTime()
        );
    }

    /**
     * @return Order[]
     */
    private function getOrders(ListOrders $listOrders): array
    {
        $product = $this->productRepository->findBy(['id' => [
            '7e3021d4-2d02-4386-8bbe-887cfe8697a8',
            '8b6d650b-7bb7-4850-bf25-36cda9bce801',
        ]]);
        $shop = $this->shopRepository->findBy(['id' => [
            'b9b1c541-d41e-4751-9ecb-4a1d823c0405',
            'cc7f5dd6-02ba-4bd9-b5c1-5b65d81e59a0',
        ]]);

        return [
            Order::fromPrimitives(
                '0fb81352-e16c-4769-892f-4a5cf33eeea1',
                self::GROUP_ID,
                self::USER_ID,
                'order 1 description',
                1,
                true,
                $listOrders,
                $product[0],
                $shop[0]
            ),
            Order::fromPrimitives(
                '8ce3b62c-b111-471a-bab1-71dafea79d51',
                self::GROUP_ID,
                self::USER_ID,
                'order 2 description',
                2,
                false,
                $listOrders,
                $product[1],
                $shop[1]
            ),
        ];
    }

    #[Test]
    public function itShouldSaveTheListOrdersInDatabase(): void
    {
        $listOrders = $this->getListOrders();

        $this->object->save([$listOrders]);

        /** @var ListOrders $listOrdersSaved */
        $listOrdersSaved = $this->object->findOneBy(['id' => $listOrders->getId()]);

        $this->assertSame($listOrders, $listOrdersSaved);
    }

    #[Test]
    public function itShouldFailListOrdersIdAlreadyExists(): void
    {
        $listOrders = $this->getListOrdersExists();

        $this->expectException(DBUniqueConstraintException::class);
        $this->object->save([$listOrders]);
    }

    #[Test]
    public function itShouldFailSavingDataBaseError(): void
    {
        /** @var MockObject&ObjectManager $objectManagerMock */
        $objectManagerMock = $this->createMock(ObjectManager::class);
        $objectManagerMock
        ->expects($this->once())
        ->method('flush')
        ->willThrowException($this->connectionException);

        $this->mockObjectManager($this->object, $objectManagerMock);

        $this->expectException(DBConnectionException::class);
        $this->object->save([$this->getListOrders()]);
    }

    #[Test]
    public function itShouldSaveTheListOrdersAndItsOrders(): void
    {
        $listOrders = $this->getListOrders();
        $orders = $this->getOrders($listOrders);
        $listOrders->setOrders($orders);

        $this->object->saveListOrdersAndOrders($listOrders);

        $listOrdersSaved = $this->object->findOneBy(['id' => $listOrders->getId()]);

        $this->assertSame($listOrders, $listOrdersSaved);
    }

    #[Test]
    public function itShouldFailSavingTheListOrdersAndItsOrdersListAlreadyExists(): void
    {
        $listOrders = $this->getListOrdersExists();
        $orders = $this->getOrders($listOrders);
        $listOrders->setOrders($orders);

        $this->expectException(DBUniqueConstraintException::class);
        $this->object->saveListOrdersAndOrders($listOrders);
    }

    #[Test]
    public function itShouldFailSavingTheListOrdersAndItsOrdersListDataBaseError(): void
    {
        /** @var MockObject&ObjectManager $objectManagerMock */
        $objectManagerMock = $this->createMock(ObjectManager::class);
        $objectManagerMock
            ->expects($this->once())
            ->method('flush')
            ->willThrowException($this->connectionException);

        $this->mockObjectManager($this->object, $objectManagerMock);

        $this->expectException(DBConnectionException::class);
        $this->object->saveListOrdersAndOrders($this->getListOrders());
    }

    #[Test]
    public function itShouldRemoveTheListOrders(): void
    {
        $listOrdersExists = $this->getListOrdersExists();
        $listOrdersToRemove = $this->object->findBy(['id' => $listOrdersExists->getId()]);
        $this->object->remove($listOrdersToRemove);

        $orderRemoved = $this->object->findBy(['id' => $listOrdersExists->getId()]);

        $this->assertEmpty($orderRemoved);
    }

    #[Test]
    public function itShouldFailRemovingDataBaseError(): void
    {
        /** @var MockObject&ObjectManager $objectManagerMock */
        $objectManagerMock = $this->createMock(ObjectManager::class);
        $objectManagerMock
        ->expects($this->once())
        ->method('flush')
        ->willThrowException($this->connectionException);

        $this->mockObjectManager($this->object, $objectManagerMock);

        $this->expectException(DBConnectionException::class);
        $this->object->remove([$this->getListOrders()]);
    }

    #[Test]
    public function itShouldFindListOrdersById(): void
    {
        $return = $this->object->findListOrderByIdOrFail($this->getListOrdersId());

        $listOrdersExpected = $this->object->findBy(['id' => self::LIST_ORDERS_ID]);
        $listOrdersActual = iterator_to_array($return);

        $this->assertCount(count($listOrdersExpected), $listOrdersActual);

        foreach ($listOrdersActual as $listOrder) {
            $this->assertContainsEquals($listOrder, $listOrdersExpected);
        }
    }

    #[Test]
    public function itShouldFindListOrdersByIdGroupId(): void
    {
        $return = $this->object->findListOrderByIdOrFail(
            $this->getListOrdersId(),
            ValueObjectFactory::createIdentifier(self::GROUP_ID)
        );

        $listOrdersExpected = $this->object->findBy(['id' => self::LIST_ORDERS_ID]);
        $listOrdersActual = iterator_to_array($return);

        $this->assertCount(count($listOrdersExpected), $listOrdersActual);

        foreach ($listOrdersActual as $listOrder) {
            $this->assertContainsEquals($listOrder, $listOrdersExpected);
        }
    }

    #[Test]
    public function itShouldFailFindingListOrdersByIdNotFound(): void
    {
        $listOrdersId = ValueObjectFactory::createIdentifier('28fbc151-06eb-4d98-8479-12188432f5d8');

        $this->expectException(DBNotFoundException::class);
        $this->object->findListOrderByIdOrFail([$listOrdersId]);
    }

    #[Test]
    public function itShouldFailFindingListOrdersByIdGroupIdNotFound(): void
    {
        $listOrdersId = $this->getListOrdersId();
        $groupId = ValueObjectFactory::createIdentifier('not found id');

        $this->expectException(DBNotFoundException::class);
        $this->object->findListOrderByIdOrFail($listOrdersId, $groupId);
    }

    #[Test]
    public function itShouldFindListOrdersByName(): void
    {
        $listOrdersName = ValueObjectFactory::createNameWithSpaces('List order name 1');
        $listOrdersExpected = $this->object->findOneBy(['name' => $listOrdersName]);
        $return = $this->object->findListOrdersByNameOrFail($listOrdersName, null);

        $this->assertEquals($listOrdersExpected, $return);
    }

    #[Test]
    public function itShouldFindListOrdersByNameNotFound(): void
    {
        $listOrdersName = ValueObjectFactory::createNameWithSpaces('List order name 1');
        $groupId = ValueObjectFactory::createIdentifier(self::GROUP_ID_2);
        $listOrdersExpected = $this->object->findOneBy(['name' => $listOrdersName]);

        $this->expectException(DBNotFoundException::class);
        $this->object->findListOrdersByNameOrFail($listOrdersName, $groupId);
    }

    #[Test]
    public function itShouldFindListOrdersByNameAndGroup(): void
    {
        $listOrdersName = ValueObjectFactory::createNameWithSpaces('List order name 1');
        $groupId = ValueObjectFactory::createIdentifier(self::GROUP_ID);
        $listOrdersExpected = $this->object->findOneBy(['name' => $listOrdersName]);
        $return = $this->object->findListOrdersByNameOrFail($listOrdersName, $groupId);

        $this->assertEquals($listOrdersExpected, $return);
    }

    #[Test]
    public function itShouldFindListOrdersOfAGroup(): void
    {
        $return = $this->object->findListOrdersGroup(
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            true
        );

        $listOrdersExpected = $this->object->findBy(['id' => self::LIST_ORDERS_ID]);
        $listOrdersActual = iterator_to_array($return);

        $this->assertCount(count($listOrdersExpected), $listOrdersActual);

        foreach ($listOrdersActual as $listOrder) {
            $this->assertContainsEquals($listOrder, $listOrdersExpected);
        }
    }

    #[Test]
    public function itShouldFailFindingListOrdersOfAGroupNotFound(): void
    {
        $this->expectException(DBNotFoundException::class);
        $this->object->findListOrdersGroup(
            ValueObjectFactory::createIdentifier('not found id'),
            true
        );
    }

    #[Test]
    public function itShouldFindListOrdersByListOrdersNameFilterEquals(): void
    {
        $listOrdersName = ValueObjectFactory::createNameWithSpaces('List order name 2');
        $filterText = ValueObjectFactory::createFilter(
            'filter_text',
            ValueObjectFactory::createFilterDbLikeComparison(FILTER_STRING_COMPARISON::EQUALS),
            $listOrdersName
        );
        $return = $this->object->findListOrderByListOrdersNameFilterOrFail(
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            $filterText,
            true
        );
        $listOrdersExpected = $this->object->findBy(['name' => $listOrdersName], ['name' => 'asc']);

        $this->assertEquals($listOrdersExpected, iterator_to_array($return));
    }

    #[Test]
    public function itShouldFindListOrdersByListOrdersNameFilterStartsWith(): void
    {
        $listOrdersName = ValueObjectFactory::createNameWithSpaces('List order');
        $filterText = ValueObjectFactory::createFilter(
            'filter_text',
            ValueObjectFactory::createFilterDbLikeComparison(FILTER_STRING_COMPARISON::STARTS_WITH),
            $listOrdersName
        );
        $return = $this->object->findListOrderByListOrdersNameFilterOrFail(
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            $filterText,
            false
        );
        $listOrdersExpected = $this->object->findBy(['name' => [
            ValueObjectFactory::createNameWithSpaces('List order name 4'),
            ValueObjectFactory::createNameWithSpaces('List order name 3'),
            ValueObjectFactory::createNameWithSpaces('List order name 2'),
            ValueObjectFactory::createNameWithSpaces('List order name 1'),
        ]],
            ['name' => 'desc']
        );

        $this->assertEquals($listOrdersExpected, iterator_to_array($return));
    }

    #[Test]
    public function itShouldFindListOrdersByListOrdersNameFilterEndsWith(): void
    {
        $listOrdersName = ValueObjectFactory::createNameWithSpaces('name 3');
        $filterText = ValueObjectFactory::createFilter(
            'filter_text',
            ValueObjectFactory::createFilterDbLikeComparison(FILTER_STRING_COMPARISON::ENDS_WITH),
            $listOrdersName
        );
        $return = $this->object->findListOrderByListOrdersNameFilterOrFail(
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            $filterText,
            true
        );
        $listOrdersExpected = $this->object->findBy([
            'name' => ValueObjectFactory::createNameWithSpaces('List order name 3'),
        ], [
            'name' => 'asc',
        ]
        );

        $this->assertEquals($listOrdersExpected, iterator_to_array($return));
    }

    #[Test]
    public function itShouldFindListOrdersByListOrdersNameFilterContains(): void
    {
        $listOrdersName = ValueObjectFactory::createNameWithSpaces('name');
        $filterText = ValueObjectFactory::createFilter(
            'filter_text',
            ValueObjectFactory::createFilterDbLikeComparison(FILTER_STRING_COMPARISON::CONTAINS),
            $listOrdersName
        );
        $return = $this->object->findListOrderByListOrdersNameFilterOrFail(
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            $filterText,
            false
        );
        $listOrdersExpected = $this->object->findBy(['name' => [
            ValueObjectFactory::createNameWithSpaces('List order name 4'),
            ValueObjectFactory::createNameWithSpaces('List order name 3'),
            ValueObjectFactory::createNameWithSpaces('List order name 2'),
            ValueObjectFactory::createNameWithSpaces('List order name 1'),
        ]],
            ['name' => 'desc']
        );

        $this->assertEquals($listOrdersExpected, iterator_to_array($return));
    }

    #[Test]
    public function itShouldFindListOrdersByProductsNameFilterEquals(): void
    {
        $productName = ValueObjectFactory::createNameWithSpaces('Juan Carlos');
        $filterText = ValueObjectFactory::createFilter(
            'filter_text',
            ValueObjectFactory::createFilterDbLikeComparison(FILTER_STRING_COMPARISON::EQUALS),
            $productName
        );
        $return = $this->object->findListOrderByProductNameFilterOrFail(
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            $filterText,
            true
        );
        $listOrdersExpected = $this->object->findBy(['id' => [
            ValueObjectFactory::createIdentifier(self::LIST_ORDERS_ID[0]),
            ValueObjectFactory::createIdentifier(self::LIST_ORDERS_ID[1]),
        ]]);

        $this->assertEquals($listOrdersExpected, iterator_to_array($return));
    }

    #[Test]
    public function itShouldFindListOrdersByProductsNameFilterStartsWith(): void
    {
        $productName = ValueObjectFactory::createNameWithSpaces('Peri');
        $filterText = ValueObjectFactory::createFilter(
            'filter_text',
            ValueObjectFactory::createFilterDbLikeComparison(FILTER_STRING_COMPARISON::STARTS_WITH),
            $productName
        );
        $return = $this->object->findListOrderByProductNameFilterOrFail(
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            $filterText,
            true
        );
        $listOrdersExpected = $this->object->findBy(['id' => ValueObjectFactory::createIdentifier(self::LIST_ORDERS_ID[3])]);

        $this->assertEquals($listOrdersExpected, iterator_to_array($return));
    }

    #[Test]
    public function itShouldFindListOrdersByProductsNameFilterEndsWith(): void
    {
        $productName = ValueObjectFactory::createNameWithSpaces('Carlos');
        $filterText = ValueObjectFactory::createFilter(
            'filter_text',
            ValueObjectFactory::createFilterDbLikeComparison(FILTER_STRING_COMPARISON::ENDS_WITH),
            $productName
        );
        $return = $this->object->findListOrderByProductNameFilterOrFail(
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            $filterText,
            true
        );

        $listOrdersExpected = $this->object->findBy(['id' => [
            ValueObjectFactory::createIdentifier(self::LIST_ORDERS_ID[0]),
            ValueObjectFactory::createIdentifier(self::LIST_ORDERS_ID[1]),
        ]]);

        $this->assertEquals($listOrdersExpected, iterator_to_array($return));
    }

    #[Test]
    public function itShouldFindListOrdersByProductsNameFilterContains(): void
    {
        $productName = ValueObjectFactory::createNameWithSpaces('Carl');
        $filterText = ValueObjectFactory::createFilter(
            'filter_text',
            ValueObjectFactory::createFilterDbLikeComparison(FILTER_STRING_COMPARISON::CONTAINS),
            $productName
        );
        $return = $this->object->findListOrderByProductNameFilterOrFail(
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            $filterText,
            true
        );

        $listOrdersExpected = $this->object->findBy(['id' => [
            ValueObjectFactory::createIdentifier(self::LIST_ORDERS_ID[0]),
            ValueObjectFactory::createIdentifier(self::LIST_ORDERS_ID[1]),
        ]]);

        $this->assertEquals($listOrdersExpected, iterator_to_array($return));
    }

    #[Test]
    public function itShouldFailFindListOrdersByProductsNameFilterContainsNotFound(): void
    {
        $productName = ValueObjectFactory::createNameWithSpaces('not found');
        $filterText = ValueObjectFactory::createFilter(
            'filter_text',
            ValueObjectFactory::createFilterDbLikeComparison(FILTER_STRING_COMPARISON::CONTAINS),
            $productName
        );
        $this->expectException(DBNotFoundException::class);
        $this->object->findListOrderByProductNameFilterOrFail(
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            $filterText,
            true
        );
    }

    #[Test]
    public function itShouldFindListOrdersByShopsNameFilterEquals(): void
    {
        $productName = ValueObjectFactory::createNameWithSpaces('Shop name 1');
        $filterText = ValueObjectFactory::createFilter(
            'filter_text',
            ValueObjectFactory::createFilterDbLikeComparison(FILTER_STRING_COMPARISON::EQUALS),
            $productName
        );
        $return = $this->object->findListOrderByShopNameFilterOrFail(
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            $filterText,
            true
        );
        $listOrdersExpected = $this->object->findBy(['id' => ValueObjectFactory::createIdentifier(self::LIST_ORDERS_ID[0])]);

        $this->assertEquals($listOrdersExpected, iterator_to_array($return));
    }

    #[Test]
    public function itShouldFindListOrdersByShopsNameFilterStartsWith(): void
    {
        $productName = ValueObjectFactory::createNameWithSpaces('Shop name');
        $filterText = ValueObjectFactory::createFilter(
            'filter_text',
            ValueObjectFactory::createFilterDbLikeComparison(FILTER_STRING_COMPARISON::STARTS_WITH),
            $productName
        );
        $return = $this->object->findListOrderByShopNameFilterOrFail(
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            $filterText,
            true
        );
        $listOrdersExpected = $this->object->findBy(['id' => [
            ValueObjectFactory::createIdentifier(self::LIST_ORDERS_ID[0]),
            ValueObjectFactory::createIdentifier(self::LIST_ORDERS_ID[1]),
            ValueObjectFactory::createIdentifier(self::LIST_ORDERS_ID[3]),
        ]]);
        $this->assertEquals($listOrdersExpected, iterator_to_array($return));
    }

    #[Test]
    public function itShouldFindListOrdersByShopsNameFilterEndsWith(): void
    {
        $productName = ValueObjectFactory::createNameWithSpaces('name 1');
        $filterText = ValueObjectFactory::createFilter(
            'filter_text',
            ValueObjectFactory::createFilterDbLikeComparison(FILTER_STRING_COMPARISON::ENDS_WITH),
            $productName
        );
        $return = $this->object->findListOrderByShopNameFilterOrFail(
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            $filterText,
            true
        );
        $listOrdersExpected = $this->object->findBy(['id' => ValueObjectFactory::createIdentifier(self::LIST_ORDERS_ID[0])]);

        $this->assertEquals($listOrdersExpected, iterator_to_array($return));
    }

    #[Test]
    public function itShouldFindListOrdersByShopsNameFilterContains(): void
    {
        $productName = ValueObjectFactory::createNameWithSpaces('name');
        $filterText = ValueObjectFactory::createFilter(
            'filter_text',
            ValueObjectFactory::createFilterDbLikeComparison(FILTER_STRING_COMPARISON::CONTAINS),
            $productName
        );
        $return = $this->object->findListOrderByShopNameFilterOrFail(
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            $filterText,
            true
        );
        $listOrdersExpected = $this->object->findBy(['id' => [
            ValueObjectFactory::createIdentifier(self::LIST_ORDERS_ID[0]),
            ValueObjectFactory::createIdentifier(self::LIST_ORDERS_ID[1]),
            ValueObjectFactory::createIdentifier(self::LIST_ORDERS_ID[3]),
        ]]);

        $this->assertEquals($listOrdersExpected, iterator_to_array($return));
    }

    #[Test]
    public function itShouldFailFindListOrdersByShopsNameFilterContainsNotFound(): void
    {
        $productName = ValueObjectFactory::createNameWithSpaces('not found');
        $filterText = ValueObjectFactory::createFilter(
            'filter_text',
            ValueObjectFactory::createFilterDbLikeComparison(FILTER_STRING_COMPARISON::CONTAINS),
            $productName
        );
        $this->expectException(DBNotFoundException::class);
        $this->object->findListOrderByShopNameFilterOrFail(
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            $filterText,
            true
        );
    }

    #[Test]
    public function itShouldFindAllListsOrdersOfAGroups(): void
    {
        $groupsId = [
            ValueObjectFactory::createIdentifier(self::GROUP_ID),
            ValueObjectFactory::createIdentifier(self::GROUP_ID_2),
        ];

        $return = $this->object->findGroupsListsOrdersOrFail($groupsId);
        $expect = $this->object->findBy([
            'groupId' => $groupsId,
        ]);

        $this->assertEquals($expect, iterator_to_array($return));
    }

    #[Test]
    public function itShouldFailNoListsOrdersFoundInGroups(): void
    {
        $groupsId = [
            ValueObjectFactory::createIdentifier('6f76bd87-7382-46fc-9746-ba983dbbfeba'),
        ];

        $this->expectException(DBNotFoundException::class);
        $this->object->findGroupsListsOrdersOrFail($groupsId);
    }

    #[Test]
    public function itShouldGetFirstLetterOfAllListOrdersInAGroup(): void
    {
        $groupId = ValueObjectFactory::createIdentifier(self::GROUP_ID);
        $expected = ['l'];

        $return = $this->object->findGroupListOrdersFirstLetterOrFail($groupId);

        $this->assertEquals($expected, $return);
    }

    #[Test]
    public function itShouldFailGetFirstLetterOfAllListOrdersInAGroupNoListOrders(): void
    {
        $groupId = ValueObjectFactory::createIdentifier(self::GROUP_ID_3);

        $this->expectException(DBNotFoundException::class);
        $this->object->findGroupListOrdersFirstLetterOrFail($groupId);
    }
}
