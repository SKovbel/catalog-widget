<?php
namespace Tisa\CatalogWidget\Model\Condition;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Rule\Model\Condition\Sql\Builder as SqlBuilder;
use Magento\Widget\Helper\Conditions;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogWidget\Model\Rule;
use Magento\CatalogWidget\Model\RuleFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Product
 * @package Tisa\CatalogWidget\Model\Condition
 */
class Product
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Product constructor.
     * @param RuleFactory $ruleFactory
     * @param SqlBuilder $sqlBuilder
     * @param Conditions $conditionsHelper
     * @param CollectionFactory $collectionFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        RuleFactory $ruleFactory,
        SqlBuilder $sqlBuilder,
        Conditions $conditionsHelper,
        CollectionFactory $collectionFactory,
        CategoryRepositoryInterface $categoryRepository,
        StoreManagerInterface $storeManager
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->sqlBuilder = $sqlBuilder;
        $this->conditionsHelper = $conditionsHelper;
        $this->collectionFactory = $collectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
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
     * @param $conditions
     * @return \Magento\Rule\Model\Condition\Combine
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

        foreach ($conditions as $key => $condition) {
            if (!empty($condition['attribute'])) {
                if (in_array($condition['attribute'], ['special_from_date', 'special_to_date'])) {
                    $conditions[$key]['value'] = date('Y-m-d H:i:s', strtotime($condition['value']));
                }
                if ($condition['attribute'] == 'category_ids') {
                    $conditions[$key] = $this->updateAnchorCategoryConditions($condition);
                }
            }
        }

        $rule = $this->ruleFactory->create();
        $rule->loadPost(['conditions' => $conditions]);
        return $rule->getConditions();
    }

    /**
     * @param array $condition
     * @return array
     */
    private function updateAnchorCategoryConditions(array $condition): array
    {
        if (array_key_exists('value', $condition)) {
            $categoryId = $condition['value'];

            try {
                $category = $this->categoryRepository->get($categoryId, $this->storeManager->getStore()->getId());
            } catch (NoSuchEntityException $e) {
                return $condition;
            }

            $children = $category->getIsAnchor() ? $category->getChildren(true) : [];
            if ($children) {
                $children = explode(',', $children);
                $condition['operator'] = "()";
                $condition['value'] = array_merge([$categoryId], $children);
            }
        }

        return $condition;
    }
}
