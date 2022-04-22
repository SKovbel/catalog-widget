<?php

namespace Tisa\CatalogWidget\Controller\Adminhtml\Category;

use Magento\Backend\App\Action\Context ;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\CatalogWidget\Controller\Adminhtml\Product\Widget;
use Tisa\CatalogWidget\Model\Rule\Category\Rule;

/**
 * Class Conditions
 */
class Conditions extends Widget
{
    /**
     * @var Rule
     */
    protected $rule;

    /**
     * Conditions constructor.
     * @param Context $context
     * @param Rule $rule
     */
    public function __construct(
        Context $context,
        Rule $rule
    ) {
        $this->rule = $rule;
        parent::__construct($context);
    }

    /**
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $typeData = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $className = $typeData[0];

        $model = $this->_objectManager->create($className)
            ->setId($id)
            ->setType($className)
            ->setRule($this->rule)
            ->setPrefix('conditions');

        if (!empty($typeData[1])) {
            $model->setAttribute($typeData[1]);
        }

        $result = '';
        if ($model instanceof AbstractCondition) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $result = $model->asHtmlRecursive();
        }

        $this->getResponse()->setBody($result);
    }
}
