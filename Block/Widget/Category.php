<?php
namespace Tisa\CatalogWidget\Block\Widget;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\Exception\LocalizedException;
use Tisa\CatalogWidget\Block\AbstractWidget;

/**
 * Class Category
 * @package Tisa\CatalogWidget\Block\Widget
 */
class Category extends AbstractWidget
{
    /**
     *
     */
    const CAT_LIMIT = 10;

    /**
     * @var string
     */
    protected $_template = 'widget/category.phtml';

    /**
     * @return int
     */
    public function getCatLimit()
    {
        return $this->getData('cat_limit') ?: self::CAT_LIMIT;
    }

    /**
     * @param string $conditionsAttribute
     * @return ProductCollection
     * @throws LocalizedException
     */
    public function getCategoryCollection($conditionsAttribute = 'conditions')
    {
        $collection = parent::getCategoryCollection($conditionsAttribute);
        $collection->setPageSize($this->getCatLimit());
        return $collection;
    }

}
