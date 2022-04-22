<?php
namespace Tisa\CatalogWidget\Model\Rule\Category;

use Magento\Backend\Helper\Data;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductCategoryList;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Eav\Model\Config;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\FormatInterface;
use Magento\Rule\Model\Condition\Context;
use Magento\Rule\Model\Condition\Product\AbstractProduct;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Db;
use Zend_Db_Expr;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection as AttributeSetCollection;

/**
 * Class Condition
 * @package Tisa\CatalogWidget\Model\Rule\Category
 */
class Condition extends AbstractProduct
{
    /**
     * @var string
     */
    protected $elementName = 'parameters';

    /**
     * @var array
     */
    protected $joinedAttributes = [];

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CategoryResource
     */
    protected $categoryResource;

    /**
     * Condition constructor.
     * @param Context $context
     * @param Data $backendData
     * @param Config $config
     * @param ProductFactory $productFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Product $productResource
     * @param AttributeSetCollection $attrSetCollection
     * @param FormatInterface $localeFormat
     * @param StoreManagerInterface $storeManager
     * @param array $data
     * @param ProductCategoryList|null $categoryList
     * @param CategoryResource $categoryResource
     */
    public function __construct(
        Context $context,
        Data $backendData,
        Config $config,
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        Product $productResource,
        AttributeSetCollection $attrSetCollection,
        FormatInterface $localeFormat,
        StoreManagerInterface $storeManager,
        array $data = [],
        ProductCategoryList $categoryList = null,
        CategoryResource $categoryResource
    ) {
        $this->storeManager = $storeManager;
        $this->categoryResource = $categoryResource;
        parent::__construct(
            $context,
            $backendData,
            $config,
            $productFactory,
            $productRepository,
            $productResource,
            $attrSetCollection,
            $localeFormat,
            $data,
            $categoryList
        );
    }

    /**
     * @param $collection
     * @return $this
     * @throws NoSuchEntityException
     */
    public function addToCollection($collection)
    {
        $attribute = $this->getAttributeObject();
        $attributeCode = $attribute->getAttributeCode();
        if ($attributeCode !== 'category_ids' && !$attribute->isStatic()) {
            $this->addAttributeToCollection($attribute, $collection);
            $attributes = $this->getRule()->getCollectedAttributes();
            $attributes[$attributeCode] = true;
            $this->getRule()->setCollectedAttributes($attributes);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function loadAttributeOptions()
    {
        $categoryAttributes = $this->categoryResource->loadAllAttributes()->getAttributesByCode();
        $categoryAttributes = array_filter(
            $categoryAttributes,
            function ($attribute) {
                return $attribute->getFrontendLabel() &&
                    $attribute->getFrontendInput() !== 'text' &&
                    $attribute->getAttributeCode() !== ProductInterface::STATUS;
            }
        );

        $attributes = [
            'category_ids' => __('Category')
        ];
        foreach ($categoryAttributes as $attribute) {
            $attributes[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
        }

        asort($attributes);
        $this->setAttributeOption($attributes);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMappedSqlField()
    {
        $result = '';
        if (in_array($this->getAttribute(), ['category_ids'])) {
            $result = 'e.entity_id';
        } elseif (isset($this->joinedAttributes[$this->getAttribute()])) {
            $result = $this->joinedAttributes[$this->getAttribute()];
        } elseif ($this->getAttributeObject()->isStatic()) {
            $result = $this->getAttributeObject()->getAttributeCode();
        } elseif ($this->getValueParsed()) {
            $result = 'e.entity_id';
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function collectValidatedAttributes($productCollection)
    {
        return $this->addToCollection($productCollection);
    }

    /**
     * @inheritDoc
     */
    public function getAttributeObject()
    {
        try {
            $obj = $this->_config->getAttribute(\Magento\Catalog\Model\Category::ENTITY, $this->getAttribute());
        } catch (\Exception $e) {
            $obj = new \Magento\Framework\DataObject();
            $obj->setEntity($this->_productFactory->create())->setFrontendInput('text');
        }
        return $obj;
    }

    /**
     * @inheritDoc
     */
    public function getBindArgumentValue()
    {
        $value = $this->getValueParsed();
        if ($this->getAttribute() != 'category_ids') {
            $value = parent::getBindArgumentValue();
        }
        return is_array($value) && $this->getMappedSqlField() === 'e.entity_id'
            ? new Zend_Db_Expr(
                $this->_productResource->getConnection()->quoteInto('?', $value, Zend_Db::INT_TYPE)
            )
            : $value;
    }

    /**
     * @param $attribute
     * @param $collection
     * @throws NoSuchEntityException
     */
    private function addAttributeToCollection($attribute, $collection): void
    {
        if ($attribute->getBackend() && $attribute->isScopeGlobal()) {
            $this->addGlobalAttribute($attribute, $collection);
        } else {
            $this->addNotGlobalAttribute($attribute, $collection);
        }
    }

    /**
     * @param Attribute $attribute
     * @param $collection
     * @return $this
     * @throws NoSuchEntityException
     */
    private function addGlobalAttribute(
        $attribute,
        $collection
    ) {
        switch ($attribute->getBackendType()) {
            case 'decimal':
            case 'datetime':
            case 'int':
                $alias = 'at_' . $attribute->getAttributeCode();
                $collection->addAttributeToSelect($attribute->getAttributeCode(), 'inner');
                break;
            default:
                $alias = 'at_' . sha1($this->getId()) . $attribute->getAttributeCode();

                $connection = $this->_productResource->getConnection();
                $storeId = $connection->getIfNullSql($alias . '.store_id', $this->storeManager->getStore()->getId());
                $linkField = $attribute->getEntity()->getLinkField();

                $collection->getSelect()->join(
                    [$alias => $collection->getTable($attribute->getBackendTable())],
                    "($alias.$linkField = e.$linkField) AND ($alias.store_id = $storeId)" .
                    " AND ($alias.attribute_id = {$attribute->getId()})",
                    []
                );
        }

        $this->joinedAttributes[$attribute->getAttributeCode()] = $alias . '.value';

        return $this;
    }

    /**
     * @param $attribute
     * @param $collection
     * @return $this
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function addNotGlobalAttribute(
        $attribute,
        $collection
    ) {
        $storeId = $this->storeManager->getStore()->getId();
        $values = $this->getAllAttributeValues($collection, $attribute);
        $validEntities = [];
        if ($values) {
            foreach ($values as $entityId => $storeValues) {
                if (isset($storeValues[$storeId])) {
                    if ($this->validateAttribute($storeValues[$storeId])) {
                        $validEntities[] = $entityId;
                    }
                } else {
                    if (isset($storeValues[Store::DEFAULT_STORE_ID]) &&
                        $this->validateAttribute($storeValues[Store::DEFAULT_STORE_ID])
                    ) {
                        $validEntities[] = $entityId;
                    }
                }
            }
        }
        $this->setOperator('()');
        $this->unsetData('value_parsed');
        if ($validEntities) {
            $this->setData('value', implode(',', $validEntities));
        } else {
            $this->unsetData('value');
        }

        return $this;
    }

    /**
     * @param CategoryCollection $collection
     * @param $attribute
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getAllAttributeValues($collection, $attribute)
    {
        /** @var $select \Magento\Framework\DB\Select */
        $select = clone $collection->getSelect();
        $attribute = $collection->getEntity()->getAttribute($attribute);

        $fieldMainTable = $collection->getConnection()->getAutoIncrementField($collection->getMainTable());
        $fieldJoinTable = $attribute->getEntity()->getLinkField();
        $select->reset()
            ->from(
                ['cpe' => $collection->getMainTable()],
                ['entity_id']
            )->join(
                ['cpa' => $attribute->getBackend()->getTable()],
                'cpe.' . $fieldMainTable . ' = cpa.' . $fieldJoinTable,
                ['store_id', 'value']
            )->where('attribute_id = ?', (int)$attribute->getId());

        $data = $collection->getConnection()->fetchAll($select);
        $res = [];

        foreach ($data as $row) {
            $res[$row['entity_id']][$row['store_id']] = $row['value'];
        }

        return $res;
    }
}
