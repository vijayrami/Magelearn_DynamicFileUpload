<?php
declare(strict_types=1);

namespace Magelearn\DynamicFileUpload\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Serialize\SerializerInterface;

class ImageConfig
{
    const XML_PATH_IMAGES = 'swatch/dynamic_row_image/images';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    
    /**
     * @var SerializerInterface
     */
    private $serializer;
    
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer
        ) {
            $this->scopeConfig = $scopeConfig;
            $this->serializer = $serializer;
    }
    
    /**
     * Get serialized images configuration
     *
     * @param string $storeId
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getImagesConfig($storeId = null): array
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_IMAGES,
            ScopeInterface::SCOPE_STORE,
            $storeId
            );
        
        try {
            return $value ? $this->serializer->unserialize($value) : [];
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }
}