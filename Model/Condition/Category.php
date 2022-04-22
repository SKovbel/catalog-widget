<?php
namespace Tisa\CatalogWidget\Model\Condition;

use Magento\Framework\Exception\LocalizedException;
use Magento\Rule\Model\Condition\Sql\Builder as SqlBuilder;
use Magento\Widget\Helper\Conditions;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogWidget\Model\RuleFactory;

/**
 * Class Category
 * @package Tisa\CatalogWidget\Model\Condition
 */
class Category
{
    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var SqlBuilder
     */
    private $sqlBuilder;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Conditions
     */
    private $conditionsHelper;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * Category constructor.
     * @param RuleFactory $ruleFactory
     * @param SqlBuilder $sqlBuilder
     * @param Conditions $conditionsHelper
     * @param CollectionFactory $collectionFactory
     * @param CategoryRepositoryInterface|null $categoryRepository
     */
    public function __construct(
        RuleFactory $ruleFactory,
        SqlBuilder $sqlBuilder,
        Conditions $conditionsHelper,
        collectionFactory $collectionFactory,
        CategoryRepositoryInterface $categoryRepository = null
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->sqlBuilder = $sqlBuilder;
        $this->conditionsHelper = $conditionsHelper;
        $this->collectionFactory = $collectionFactory;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param $conditionData
     * @return Collection
     * @throws LocalizedException
     */
    public function getCollection($conditionData)
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $conditions = $this->getConditions($conditionData);
        if ($conditions) {
            $conditions->collectValidatedAttributes($collection);
            $this->sqlBuilder->attachConditionToCollection($collection, $conditions);
        }
        return $collection;
    }

    /**
     * @return mixed
     */
    private function getConditions($conditions)
    {
        if (!$conditions) {
            return null;
        }

        try {
            $conditions = $this->conditionsHelper->decode($conditions);
        } catch (Exception $e) {
            return null;
        }

        $rule = $this->ruleFactory->create();
        $rule->loadPost(['conditions' => $conditions]);
        return $rule->getConditions();
    }
}
