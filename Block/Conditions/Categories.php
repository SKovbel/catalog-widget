<?php
namespace Tisa\CatalogWidget\Block\Conditions;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Data\Form\Element\Text;
use Magento\Framework\Registry;
use Magento\Rule\Block\Conditions;
use Tisa\CatalogWidget\Model\Rule\Category\Rule;

/**
 * Class Categories
 * @package Tisa\CatalogWidget\Block\Conditions
 */
class Categories extends Template implements RendererInterface
{
    /**
     * @var Conditions
     */
    protected $conditions;

    /**
     * @var Rule
     */
    protected $rule;

    /**
     * @var Facto
     * @var \Magento\CatalogWidget\Model\Rule
     */
    protected $elementFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var AbstractElement
     */
    protected $element;

    /**
     * @var Text
     */
    protected $input;

    /**
     * @var string
     */
    protected $_template = 'Tisa_CatalogWidget::category/conditions.phtml';

    /**
     * Conditions constructor.
     * @param Context $context
     * @param Factory $elementFactory
     * @param Conditions $conditions
     * @param Rule $rule
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        Conditions $conditions,
        Rule $rule,
        Registry $registry,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->conditions = $conditions;
        $this->rule = $rule;
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $widgetParameters = [];
        $widget = $this->registry->registry('current_widget_instance');
        if ($widget) {
            $widgetParameters = $widget->getWidgetParameters();
        } elseif ($widgetOptions = $this->getLayout()->getBlock('wysiwyg_widget.options')) {
            $widgetParameters = $widgetOptions->getWidgetValues();
        }

        if (isset($widgetParameters['conditions'])) {
            $this->rule->loadPost($widgetParameters);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function render(AbstractElement $element)
    {
        $this->element = $element;
        $this->rule->getConditions()->setJsFormObject($this->getHtmlId());
        return $this->toHtml();
    }

    /**
     * @return string
     */
    public function getNewChildUrl()
    {
        return $this->getUrl(
            'tisa_catalogwidget/category/conditions/form/' . $this->getElement()->getContainer()->getHtmlId()
        );
    }

    /**
     * @return AbstractElement
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * @return string
     */
    public function getHtmlId()
    {
        return $this->getElement()->getContainer()->getHtmlId();
    }

    /**
     * @return string
     */
    public function getInputHtml()
    {
        $this->input = $this->elementFactory->create('text');
        $this->input->setRule($this->rule)->setRenderer($this->conditions);
        //$this->input->setId($this->element->getId());
        return $this->input->toHtml();
    }
}
