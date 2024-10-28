<?php

namespace Magelearn\DynamicFileUpload\Block\Adminhtml\Form\Field;

class ImageFieldsList extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    protected $imageRenderer;

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addImageColumn();
        $this->addNameColumn();
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Category');
        parent::_prepareToRender();
    }

    private function addImageColumn()
    {
        $this->addColumn('image', [
            'label' => __('Category Image'),
            'renderer' => $this->getImageRenderer(),
            'class' => 'required-entry'
        ]);
    }
    
    private function addNameColumn()
    {
        $this->addColumn('name', [
            'label' => __('Category Name'),
            'class' => 'required-entry'
        ]);
    }

    protected function getImageRenderer()
    {
        if (!$this->imageRenderer) {
            $this->imageRenderer = $this->getLayout()->createBlock(
                \Magelearn\DynamicFileUpload\Block\Adminhtml\Form\Field\ImageColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->imageRenderer;
    }
}