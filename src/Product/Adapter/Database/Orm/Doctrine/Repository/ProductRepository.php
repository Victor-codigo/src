<?php

declare(strict_types=1);

namespace Product\Adapter\Database\Orm\Doctrine\Repository;

use Common\Adapter\Database\Orm\Doctrine\Repository\RepositoryBase;
use Common\Domain\Database\Orm\Doctrine\Repository\Exception\DBConnectionException;
use Common\Domain\Database\Orm\Doctrine\Repository\Exception\DBNotFoundException;
use Common\Domain\Database\Orm\Doctrine\Repository\Exception\DBUniqueConstraintException;
use Common\Domain\Model\ValueObject\Group\Filter;
use Common\Domain\Model\ValueObject\String\Identifier;
use Common\Domain\Model\ValueObject\String\NameWithSpaces;
use Common\Domain\Ports\Paginator\PaginatorInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\Exception\EntityIdentityCollisionException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Product\Domain\Model\Product;
use Product\Domain\Model\ProductShop;
use Product\Domain\Port\Repository\ProductRepositoryInterface;
use Shop\Domain\Model\Shop;

/**
 * @phpstan-extends RepositoryBase<Product>
 */
class ProductRepository extends RepositoryBase implements ProductRepositoryInterface
{
    /**
     * @param PaginatorInterface<int, object> $paginator
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        PaginatorInterface $paginator,
    ) {
        parent::__construct($managerRegistry, Product::class, $paginator);
    }

    /**
     * @throws DBUniqueConstraintException
     * @throws DBConnectionException
     */
    public function save(Product $product): void
    {
        try {
            $this->objectManager->persist($product);
            $this->objectManager->flush();
        } catch (UniqueConstraintViolationException|EntityIdentityCollisionException $e) {
            throw DBUniqueConstraintException::fromId($product->getId()->getValue(), $e->getCode());
        } catch (\Exception $e) {
            throw DBConnectionException::fromConnection($e->getCode());
        }
    }

    /**
     * @param Product[] $products
     *
     * @throws DBConnectionException
     */
    public function remove(array $products): void
    {
        try {
            foreach ($products as $product) {
                $this->objectManager->remove($product);
            }

            $this->objectManager->flush();
        } catch (\Exception $e) {
            throw DBConnectionException::fromConnection($e->getCode());
        }
    }

    /**
     * @return PaginatorInterface<int, Product>
     *
     * @throws DBNotFoundException
     */
    public function findProductsByGroupAndNameOrFail(Identifier $groupId, NameWithSpaces $name): PaginatorInterface
    {
        $queryBuilder = $this->entityManager
            ->createQueryBuilder()
            ->select('product')
            ->from(Product::class, 'product')
            ->where('product.groupId = :groupId')
            ->setParameter('groupId', $groupId);

        if (!$name->isNull()) {
            $queryBuilder
                ->andWhere('product.name = :name')
                ->setParameter('name', $name);
        }

        $paginator = $this->paginator->createPaginator($queryBuilder);

        if (0 === $paginator->getItemsTotal()) {
            throw DBNotFoundException::fromMessage('Products not found');
        }

        return $paginator;
    }

    /**
     * @param Identifier[]|null $productsId
     * @param Identifier[]|null $shopsId
     *
     * @return PaginatorInterface<int, Product>
     *
     * @throws DBNotFoundException
     */
    public function findProductsOrFail(Identifier $groupId, ?array $productsId = null, ?array $shopsId = null, bool $orderAsc = true): PaginatorInterface
    {
        $query = $this->entityManager
            ->createQueryBuilder()
            ->select('product')
            ->from(Product::class, 'product')
            ->where('product.groupId = :groupId')
            ->setParameter('groupId', $groupId);

        if (!empty($productsId)) {
            $query
                ->andWhere('product.id IN (:productId)')
                ->setParameter('productId', $productsId);
        }

        if (!empty($shopsId)) {
            $query
                ->leftJoin(ProductShop::class, 'productShop', Join::WITH, 'product.id = productShop.productId')
                ->leftJoin(Shop::class, 'shop', Join::WITH, 'productShop.shopId = shop.id')
                ->andWhere('shop.id IN (:shopId)')
                ->setParameter('shopId', $shopsId);
        }

        $query->orderBy('product.name', $orderAsc ? 'ASC' : 'DESC');

        return $this->queryPaginationOrFail($query);
    }

    /**
     * @return PaginatorInterface<int, Product>
     *
     * @throws DBNotFoundException
     */
    public function findProductsByProductNameOrFail(Identifier $groupId, NameWithSpaces $productName, bool $orderAsc = true): PaginatorInterface
    {
        $productEntity = Product::class;
        $orderBy = $orderAsc ? 'ASC' : 'DESC';
        $dql = <<<DQL

        SELECT product
        FROM {$productEntity} product
        WHERE product.groupId = :groupId
            AND product.name = :productName
        ORDER BY product.name {$orderBy}

        DQL;

        return $this->dqlPaginationOrFail($dql, [
            'groupId' => $groupId,
            'productName' => $productName,
        ]);
    }

    /**
     * @return PaginatorInterface<int, Product>
     *
     * @throws DBNotFoundException
     */
    public function findProductsByProductNameFilterOrFail(Identifier $groupId, Filter $productNameFilter, bool $orderAsc = true): PaginatorInterface
    {
        $productEntity = Product::class;
        $orderBy = $orderAsc ? 'ASC' : 'DESC';
        $dql = <<<DQL

        SELECT product
        FROM {$productEntity} product
        WHERE product.groupId = :groupId
            AND product.name LIKE :productNameFilter
        ORDER BY product.name {$orderBy}

        DQL;

        return $this->dqlPaginationOrFail($dql, [
            'groupId' => $groupId,
            'productNameFilter' => $productNameFilter->getValueWithFilter(),
        ]);
    }

    /**
     * @return PaginatorInterface<int, Product>
     *
     * @throws DBNotFoundException
     */
    public function findProductsByShopNameFilterOrFail(Identifier $groupId, Filter $shopNameFilter, bool $orderAsc = true): PaginatorInterface
    {
        $productEntity = Product::class;
        $productShopEntity = ProductShop::class;
        $shopEntity = Shop::class;
        $orderBy = $orderAsc ? 'ASC' : 'DESC';
        $dql = <<<DQL

        SELECT product
        FROM {$productEntity} product
            LEFT JOIN {$productShopEntity} productShop WITH product.id = productShop.productId
            LEFT JOIN {$shopEntity} shop WITH productShop.shopId = shop.id
        WHERE product.groupId = :groupId
            AND shop.name LIKE :shopNameFilter
        ORDER BY product.name {$orderBy}

        DQL;

        return $this->dqlPaginationOrFail($dql, [
            'groupId' => $groupId,
            'shopNameFilter' => $shopNameFilter->getValueWithFilter(),
        ]);
    }

    /**
     * @param Identifier[] $groupsId
     *
     * @return PaginatorInterface<int, Product>
     *
     * @throws DBNotFoundException
     */
    public function findGroupsProductsOrFail(array $groupsId): PaginatorInterface
    {
        $productEntity = Product::class;
        $dql = <<<DQL
            SELECT products
            FROM {$productEntity} products
            WHERE products.groupId IN (:groupsId)
        DQL;

        return $this->dqlPaginationOrFail($dql, [
            'groupsId' => $groupsId,
        ]);
    }

    /**
     * @return string[]
     *
     * @throws DBNotFoundException
     */
    public function findGroupProductsFirstLetterOrFail(Identifier $groupId): array
    {
        $productEntity = Product::class;
        $dql = <<<DQL
            SELECT DISTINCT SUBSTRING(product.name, 1, 1) AS firstLetter
            FROM {$productEntity} product
            WHERE product.groupId = :groupId
            GROUP BY firstLetter
            ORDER BY firstLetter ASC
        DQL;

        $queryResult = $this->entityManager
            ->createQuery($dql)
            ->setParameter('groupId', $groupId)
            ->getArrayResult();

        if (empty($queryResult)) {
            throw DBNotFoundException::fromMessage('Products not found');
        }

        return array_map(
            fn (array $row): string => mb_strtolower($row['firstLetter']),
            $queryResult
        );
    }
}
