<?php
namespace Tisa\CatalogWidget\Block\Widget;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\Exception\LocalizedException;
use Tisa\CatalogWidget\Block\AbstractWidget;

/**
 * Class Product
 * @package Tisa\CatalogWidget\Block\Widget
 */
class Product extends AbstractWidget
{
    /**
     *
     */
    const VISIBLE_SLIDES_DEF = 5;
    const PROD_LIMIT = 7;

    /**
     * @var string
     */
    protected $_template = 'widget/product.phtml';

    /**
     * @return int
     */
    public function getProdLimit()
    {
        return $this->getData('prod_limit') ?: self::PROD_LIMIT;
    }

    /**
     * @return int
     */
    public function getShowSlider()
    {
        return $this->getData('show_slider') ?: false;
    }

    /**
     * @return int
     */
    public function getVisibleSlides()
    {
        return $this->getData('visible_slides') ?: self::VISIBLE_SLIDES_DEF;
    }

    /**
     * @param string $conditionsAttribute
     * @return ProductCollection
     * @throws LocalizedException
     */
    public function getProductCollection($conditionsAttribute = 'conditions')
    {
        $collection = parent::getProductCollection($conditionsAttribute);
        $collection->setPageSize($this->getProdLimit());
        return $collection;
    }
}