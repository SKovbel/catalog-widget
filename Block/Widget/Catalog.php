<?php
namespace Tisa\CatalogWidget\Block\Widget;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Tisa\CatalogWidget\Block\AbstractWidget;

/**
 * Class Catalog
 * @package Tisa\CatalogWidget\Block\Widget
 */
class Catalog extends AbstractWidget
{
    /**
     *
     */
    const CAT_LIMIT = 5;
    const PROD_LIMIT = 5;
    const TMPL_WITH_TAB = 'widget/catalog.phtml';

    /**
     * @inheritDoc
     */
    public function getTemplate()
    {
        return self::TMPL_WITH_TAB;
    }

    /**
     * @return int
     */
    public function getCatLimit()
    {
        return $this->getData('cat_limit') ?: self::CAT_LIMIT;
    }

    /**
     * @return int
     */
    public function getProdLimit()
    {
        return $this->getData('prod_limit') ?: self::PROD_LIMIT;
    }

    /**
     * @param string $conditionsAttribute
     * @return ProductCollection
     * @throws LocalizedException
     */
    public function getCollection()
    {
        $productIds = $this->getData('products');
        $categoryIds = $this->getData('categories');
        if (!$productIds || !$categoryIds) {
            return [];
        }

        $categories = $this->getCategories();
        $products = $this->getProducts();

        $categoryIds = explode(',', trim($categoryIds));
        $productGroups = explode(';', trim($productIds));

        $collection = [];
        foreach ($categoryIds as $idx => $categoryId) {
            $category = $categories[$categoryId] ?? null;
            $productIds = $productGroups[$idx] ?? null;
            $productIds = explode(',', trim($productIds));

            if (!$category || !$productIds) {
                continue;
            }

            $prods = [];
            foreach ($productIds as $productId) {
                $prods[] = $products[(int)$productId] ?? null;
            }
            $prods = array_slice(array_filter($prods), 0, $this->getProdLimit());
            if ($prods) {
                $collection[] = ['cat' => $category, 'prods' => $prods];
            }
        }
        return array_slice($collection, 0, $this->getCatLimit());
    }

    /**
     * @return Product[]
     * @throws LocalizedException
     */
    private function getProducts()
    {
        $collection = $this->getProductCollection(null);
        $collection->setPageSize(2 * $this->getCatLimit() * $this->getProdLimit());

        $products = [];
        /** @var Product $product */
        foreach ($collection as $product) {
            $products[$product->getId()] = $product;
        }
        return $products;
    }


    /**
     * @return Category[]
     * @throws LocalizedException
     */
    private function getCategories()
    {
        $collection = $this->getCategoryCollection(null);
        $collection->setPageSize(2 * $this->getCatLimit());

        $categories = [];
        /** @var Category $category */
        foreach ($collection as $category) {
            $categories[$category->getId()] = $category;
        }
        return $categories;
    }
}