<?php

namespace Magelearn\DynamicFileUpload\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\AbstractBlock;
use Magelearn\DynamicFileUpload\Block\Adminhtml\ImageButton;

class ImageColumn extends AbstractBlock
{
    public function setInputName(string $value)
    {
        return $this->setName($value);
    }
    
    public function setInputId(string $value)
    {
        return $this->setId($value);
    }
    
    protected function _toHtml(): string
    {
        $imageButton = $this->getLayout()
        ->createBlock(ImageButton::class)
        ->setData('id', $this->getId())
        ->setData('name', $this->getName());
        return $imageButton->toHtml();
    }
    
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '';
        
        // Assuming 'image' is the field name where the image URL is stored
        $imageUrl = $element->getValue();
        
        if ($imageUrl) {
            $html .= '<img src="' . $imageUrl . '" alt="' . __('Category Image') . '" style="max-width: 100px; max-height: 100px;" />';
        } else {
            $html .= __('No Image');
        }
        
        return $html;
    }
}
