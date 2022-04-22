<?php
namespace Tisa\CatalogWidget\Block;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Widget\Block\BlockInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\AbstractProduct as ParentAbstractProduct;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Tisa\CatalogWidget\Model\Condition\Product as ProductConditions;
use Tisa\CatalogWidget\Model\Condition\Category as CategoryConditions;

/**
 * Class AbstractWidget
 * @package Tisa\CatalogWidget\Block
 */
abstract class AbstractWidget extends ParentAbstractProduct implements BlockInterface, IdentityInterface
{
    /**
     * @var Image
     */
    private $image;

    /**
     * @var ProductConditions
     */
    private $productConditions;

    /**
     * @var CategoryConditions
     */
    private $categoryConditions;

    /**
     * AbstractCatalog constructor.
     * @param Context $context
     * @param Image $image
     * @param CategoryConditions $categoryConditions
     * @param ProductConditions $productConditions
     * @param $data
     */
    public function __construct(
        Context $context,
        Image $image,
        CategoryConditions $categoryConditions,
        ProductConditions $productConditions,
        $data
    ) {
        parent::__construct($context, $data);
        $this->image = $image;
        $this->categoryConditions = $categoryConditions;
        $this->productConditions = $productConditions;
    }

    /**
     * @param string $conditionsAttribute
     * @return ProductCollection
     * @throws LocalizedException
     */
    public function getProductCollection($conditionsAttribute = 'conditions')
    {
        $conditions = $this->getData($conditionsAttribute . '_encoded') ?: $this->getData($conditionsAttribute);
        $collection = $this->productConditions->getCollection($conditions);
        $collection
            ->addAttributeToSelect('*')
            ->setVisibility([ProductVisibility::VISIBILITY_IN_SEARCH, ProductVisibility::VISIBILITY_IN_CATALOG, ProductVisibility::VISIBILITY_BOTH])
            ->addAttributeToFilter('status', ProductStatus::STATUS_ENABLED)
            ->addStoreFilter()
            ->setCurPage(1);

        if (($productIds = $this->getData('products'))) {
            $productIds = explode(',', str_replace(';', ',', $productIds));
            $productIds = array_map('intval', $productIds);
            $collection->addAttributeToFilter('entity_id', ['in' => $productIds]);
        }

        return $collection;
    }

    /**
     * @param string $conditionsAttribute
     * @return CategoryCollection
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCategoryCollection($conditionsAttribute = 'conditions')
    {
        $conditions = $this->getData($conditionsAttribute . '_encoded') ?: $this->getData($conditionsAttribute);
        $collection = $this->categoryConditions->getCollection($conditions);
        $collection
            ->addAttributeToSelect('*')
            ->setStore($this->_storeManager->getStore())
            ->setCurPage(1);

        if (($categoryIds = $this->getData('categories'))) {
            $categoryIds = explode(',', str_replace(';', ',', $categoryIds));
            $categoryIds = array_map('intval', $categoryIds);
            $collection->addAttributeToFilter('entity_id', ['in' => $categoryIds]);
        }

        return $collection;
    }

    /**
     * @param ProductInterface $product
     * @return string
     */
    public function getProductImageUrl(ProductInterface $product)
    {
        $this->image
            ->init($product, 'product_base_image')
            ->constrainOnly(true)
            ->keepAspectRatio(true)
            ->keepTransparency(true)
            ->keepFrame(false)
            ->resize(150, 150);
        return $this->image->getUrl();
    }

    /**
     * @param CategoryInterface $category
     * @return string
     */
    public function getCategoryImageUrl(CategoryInterface $category)
    {
        return $category->getImageUrl() ?: $this->image->getDefaultPlaceholderUrl('image');
    }

    /**
     * @inheritDoc
     */
    public function getIdentities()
    {
        $identities = [];
        if ($this->getProductCollection()) {
            foreach ($this->getProductCollection() as $product) {
                if ($product instanceof IdentityInterface) {
                    $identities[] = $product->getIdentities();
                }
            }
        }
        $identities = array_merge([], ...$identities);

        return $identities ?: [Product::CACHE_TAG];
    }
}