<?php

declare(strict_types=1);

namespace Group\Application\GroupUserGetGroups\Dto;

use Common\Domain\Model\ValueObject\Group\Filter;
use Common\Domain\Model\ValueObject\Integer\PaginatorPage;
use Common\Domain\Model\ValueObject\Integer\PaginatorPageItems;
use Common\Domain\Model\ValueObject\ValueObjectFactory;
use Common\Domain\Security\UserShared;
use Common\Domain\Service\ServiceInputDtoInterface;
use Common\Domain\Validation\Common\VALIDATION_ERRORS;
use Common\Domain\Validation\Filter\FILTER_SECTION;
use Common\Domain\Validation\Filter\FILTER_STRING_COMPARISON;
use Common\Domain\Validation\Group\GROUP_TYPE;
use Common\Domain\Validation\ValidationInterface;

class GroupUserGetGroupsInputDto implements ServiceInputDtoInterface
{
    public readonly UserShared $userSession;
    public readonly PaginatorPage $page;
    public readonly PaginatorPageItems $pageItems;
    public readonly ?GROUP_TYPE $groupType;
    public readonly ?Filter $filterSection;
    public readonly ?Filter $filterText;
    public readonly bool $orderAsc;

    public function __construct(UserShared $userSession, ?int $page, ?int $pageItems, ?string $groupType, ?string $filterSection, ?string $filterText, ?string $filterValue, bool $orderAsc)
    {
        $this->userSession = $userSession;
        $this->page = ValueObjectFactory::createPaginatorPage($page);
        $this->pageItems = ValueObjectFactory::createPaginatorPageItems($pageItems);
        $this->groupType = $this->getGroupType($groupType);

        $this->filterSection = null === $filterSection ? null : ValueObjectFactory::createFilter(
            'filter_section',
            ValueObjectFactory::createFilterSection(FILTER_SECTION::tryFrom($filterSection)),
            ValueObjectFactory::createNameWithSpaces($filterValue)
        );
        $this->filterText = null === $filterText ? null : ValueObjectFactory::createFilter(
            'text_filter',
            ValueObjectFactory::createFilterDbLikeComparison(FILTER_STRING_COMPARISON::tryFrom($filterText)),
            ValueObjectFactory::createNameWithSpaces($filterValue)
        );
        $this->orderAsc = $orderAsc;
    }

    public function validate(ValidationInterface $validator): array
    {
        $errorList = $validator->validateValueObjectArray([
            'page' => $this->page,
            'page_items' => $this->pageItems,
        ]);

        $errorListFilterSection = null === $this->filterSection
            ? []
            : $this->validateFilter($validator, $this->filterSection, 'section');
        $errorListFilterTest = null === $this->filterText
            ? []
            : $this->validateFilter($validator, $this->filterText, 'text');

        if (null !== $this->filterSection && null === $this->filterText
        || null === $this->filterSection && null !== $this->filterText) {
            $errorList['filter_section_and_text_not_empty'] = [VALIDATION_ERRORS::NOT_NULL];
        }

        if (!empty($errorListFilterSection)) {
            $errorList = array_merge($errorList, $errorListFilterSection);
        }

        if (!empty($errorListFilterTest)) {
            $errorList = array_merge($errorList, $errorListFilterTest);
        }

        return $errorList;
    }

    private function validateFilter(ValidationInterface $validator, Filter $filter, string $errorPrefix): array
    {
        if ($filter->getFilter()->isNull()
        && $filter->isNull()) {
            return [];
        }

        $errorList = [];
        $errorListFilter = $filter->validate($validator);

        if (!empty($errorListFilter)
        && array_key_exists('type', $errorListFilter)) {
            $errorList["{$errorPrefix}_filter_type"] = $errorListFilter['type'];
        }

        if (!empty($errorListFilter)
        && array_key_exists('value', $errorListFilter)) {
            $errorList["{$errorPrefix}_filter_value"] = $errorListFilter['value'];
        }

        return $errorList;
    }

    private function getGroupType(?string $groupType): ?GROUP_TYPE
    {
        if (null === $groupType) {
            return null;
        }

        if ('USER' === mb_strtoupper($groupType)) {
            return GROUP_TYPE::USER;
        }

        if ('GROUP' === mb_strtoupper($groupType)) {
            return GROUP_TYPE::GROUP;
        }

        return null;
    }
}
