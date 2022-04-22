<?php
namespace Tisa\CatalogWidget\Model\Rule\Category;

use Magento\Rule\Model\Condition\Combine as ParentCombine;
use Magento\Rule\Model\Condition\Context;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogWidget\Model\Rule\Condition\ProductFactory;

/**
 * Combination of product conditions
 */

class Combine extends ParentCombine
{
    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * {@inheritdoc}
     */
    protected $elementName = 'parameters';

    /**
     * @var array
     */
    private $excludedAttributes;

    /**
     * Combine constructor.
     * @param Context $context
     * @param ConditionFactory $conditionFactory
     * @param array $data
     * @param array $excludedAttributes
     */
    public function __construct(
        Context $context,
        ConditionFactory $conditionFactory,
        array $data = [],
        array $excludedAttributes = []
    ) {
        $this->productFactory = $conditionFactory;
        parent::__construct($context, $data);
        $this->setType(Combine::class);
        $this->excludedAttributes = $excludedAttributes;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return 'conditions';
    }

    /**
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $productAttributes = $this->productFactory->create()->loadAttributeOptions()->getAttributeOption();
        $attributes = [];
        foreach ($productAttributes as $code => $label) {
            if (!in_array($code, $this->excludedAttributes)) {
                $attributes[] = [
                    'value' => Condition::class . '|' . $code,
                    'label' => $label,
                ];
            }
        }
        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive(
            $conditions,
            [
                [
                    'value' => Combine::class,
                    'label' => __('Conditions Combination'),
                ],
                ['label' => __('Category Attribute'), 'value' => $attributes]
            ]
        );
        return $conditions;
    }

    /**
     * Collect validated attributes for Product Collection
     *
     * @param Collection $productCollection
     * @return $this
     */
    public function collectValidatedAttributes($productCollection)
    {
        foreach ($this->getConditions() as $condition) {
            $condition->collectValidatedAttributes($productCollection);
        }
        return $this;
    }
}
