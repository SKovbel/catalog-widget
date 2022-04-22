<?php
namespace Tisa\CatalogWidget\Block\Widget;

use Tisa\CatalogWidget\Block\AbstractWidget;

/**
 * Class SubCategory
 * @package Tisa\CatalogWidget\Block\Widget
 */
class SubCategory extends AbstractWidget
{
    /**
     *
     */
    const LIMIT_CAT = 5;
    const LIMIT_SUB = 10;

    /**
     * @var string
     */
    protected $_template = 'widget/sub-category.phtml';

    /**
     * @return int
     */
    public function getCatLimit()
    {
        return $this->getData('cat_limit') ?: self::LIMIT_CAT;
    }

    /**
     * @return int
     */
    public function getSubLimit()
    {
        return $this->getData('sub_limit') ?: self::LIMIT_SUB;
    }

    /**
     *
     */
    public function getCategories()
    {
        $collection = $this->getCategoryCollection()
            ->setPageSize(2 * $this->getCatLimit() * $this->getSubLimit());

        $_categories = [];
        foreach ($collection as $category) {
            $_categories[$category->getId()] = $category;
        }

        $categories = [];
        foreach (explode(';', $this->getData('categories')) as $groupIds) {
            $sub = [];
            foreach (explode(',', $groupIds) as $id) {
                $sub[] = $_categories[(int) $id] ?? null;
            }
            $categories[] = array_slice(array_filter($sub), 0, $this->getSubLimit());
        }
        return array_slice($categories, 0, $this->getCatLimit());
    }
}
