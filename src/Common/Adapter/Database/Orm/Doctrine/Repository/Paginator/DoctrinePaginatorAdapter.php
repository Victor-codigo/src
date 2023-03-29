<?php

declare(strict_types=1);

namespace Common\Adapter\Database\Orm\Doctrine\Repository\Paginator;

use Common\Adapter\Database\Orm\Doctrine\Repository\Paginator\Exception\PaginatorPageException;
use Common\Domain\Config\AppConfig;
use Common\Domain\Exception\InvalidArgumentException;
use Common\Domain\Exception\LogicException;
use Common\Domain\Ports\Paginator\PaginatorInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

class DoctrinePaginatorAdapter implements PaginatorInterface
{
    public const MAX_RESULT_DEFAULT = AppConfig::PAGINATION_PAGE_ITEMS_MAX;

    private Paginator|null $paginator = null;

    public function createPaginator(Query|QueryBuilder $query): self
    {
        $paginator = new self();
        $paginator->setNewDoctrinePaginator($query);

        return $paginator;
    }

    private function setNewDoctrinePaginator(Query|QueryBuilder $query): void
    {
        $this->paginator = new Paginator($query);
    }

    private function setPageItems(int $pageItems = self::MAX_RESULT_DEFAULT): self
    {
        if ($pageItems <= 0) {
            throw InvalidArgumentException::fromMessage('Page items must be bigger than 0');
        }

        $this->paginator
            ->getQuery()
            ->setMaxResults($pageItems);

        return $this;
    }

    public function getPageItems(): int
    {
        $this->validateQuery();

        return $this->paginator
            ->getQuery()
            ->getMaxResults();
    }

    private function setPage(int $page): self
    {
        if ($page <= 0) {
            throw PaginatorPageException::formMessage('Wrong page. Page must be bigger than 1');
        }

        if ($page > $this->getPagesTotal()) {
            throw PaginatorPageException::formMessage("Wrong page. Page must be lower than pages total: {$this->getPagesTotal()}");
        }

        $this->paginator
            ->getQuery()
            ->setFirstResult($this->getPageOffset($page));

        return $this;
    }

    public function setPagination(int $page = 1, int $pageItems = self::MAX_RESULT_DEFAULT): self
    {
        $this->validateQuery();

        $this
            ->setPageItems($pageItems)
            ->setPage($page);

        return $this;
    }

    public function getPageCurrent(): int
    {
        $this->validateQuery();

        $pageItems = $this->getPageItems();
        $firstResult = $this->paginator
            ->getQuery()
            ->getFirstResult();

        return $firstResult < $pageItems
                ? 1
                : (int) floor($firstResult / $pageItems) + 1;
    }

    public function getPagesTotal(): int
    {
        $this->validateQuery();

        $itemsTotal = $this->getItemsTotal();

        return 0 !== $itemsTotal
            ? (int) ceil($itemsTotal / $this->getPageItems())
            : 1;
    }

    public function hasNext(): bool
    {
        $this->validateQuery();

        return $this->getPageCurrent() < $this->getPagesTotal();
    }

    public function hasPrevious(): bool
    {
        $this->validateQuery();

        return $this->getPageCurrent() > 1;
    }

    public function getPageNextNumber(): int|null
    {
        $this->validateQuery();

        if (!$this->hasNext()) {
            return null;
        }

        return $this->getPageCurrent() + 1;
    }

    public function getPagePreviousNumber(): int|null
    {
        $this->validateQuery();

        if (!$this->hasPrevious()) {
            return null;
        }

        return $this->getPageCurrent() - 1;
    }

    public function getItemsTotal(): int
    {
        $this->validateQuery();

        return count($this->paginator);
    }

    public function count(): int
    {
        return $this->getItemsTotal();
    }

    public function getIterator(): \Traversable
    {
        $this->validateQuery();

        return $this->paginator->getIterator();
    }

    private function getPageOffset(int $page): int
    {
        $this->validateQuery();

        return ($page - 1) * $this->getPageItems();
    }

    private function validateQuery(): void
    {
        if (null === $this->paginator) {
            throw LogicException::fromMessage('Query not set. Use method setQuery, to set the query first');
        }
    }
}
