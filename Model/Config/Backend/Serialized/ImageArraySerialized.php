<?php
declare(strict_types=1);

namespace Magelearn\DynamicFileUpload\Model\Config\Backend\Serialized;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Config\Model\Config\Backend\Serialized\ArraySerialized;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class ImageArraySerialized extends ArraySerialized
{
    /**
     * @var \Magelearn\DynamicFileUpload\Model\ImageUploaderFactory
     */
    private $imageUploaderFactory;
    
    /**
     * @var \Magelearn\DynamicFileUpload\Model\Config\ImageConfig
     */
    private $imageConfig;
    
    /**
     * @var Filesystem
     */
    protected $filesystem;
    
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private const UPLOAD_DIR = 'magelearn/dynamicfileupload';
    
    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magelearn\DynamicFileUpload\Model\Config\ImageConfig $imageConfig
     * @param \Magelearn\DynamicFileUpload\Model\ImageUploaderFactory $imageUploaderFactory
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @param Json|null $serializer
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magelearn\DynamicFileUpload\Model\Config\ImageConfig $imageConfig,
        \Magelearn\DynamicFileUpload\Model\ImageUploaderFactory $imageUploaderFactory,
        Filesystem $filesystem,
        LoggerInterface $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        Json $serializer = null
    ) {
        $this->imageUploaderFactory = $imageUploaderFactory;
        $this->filesystem = $filesystem;
        $this->imageConfig = $imageConfig;
        $this->logger = $logger;
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data,
            $serializer
        );
    }

    /**
     * Process data before saving
     *
     * @return ArraySerialized
     * @throws LocalizedException
     */
    public function beforeSave(): ArraySerialized
    {
        try {
            $value = $this->getValue();
            if (is_array($value)) {
                $value = $this->processImageUpload($value);
                $this->setValue($value);
            }
            return parent::beforeSave();
        } catch (\Exception $e) {
            throw new LocalizedException(__('Error processing images: %1', $e->getMessage()));
        }
    }
    /**
     * Process image upload and map rows
     *
     * @param array $rows
     * @return array
     * @throws LocalizedException
     */
    private function processImageUpload(array $rows): array
    {
        try {
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $destinationPath = $mediaDirectory->getAbsolutePath(self::UPLOAD_DIR);
            
            // Create image uploader instance
            $imageUploader = $this->imageUploaderFactory->create([
                'path' => $this->getPath(),
                'uploadDir' => $destinationPath,
            ]);
            
            // Upload files and get results
            $uploadedFiles = $imageUploader->upload();
            
            // Get existing configuration
            $existingConfig = $this->imageConfig->getImagesConfig();
            
            return $this->processRows($rows, $uploadedFiles, $existingConfig);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Failed to process image upload: %1', $e->getMessage()));
        }
    }

    /**
     * Process rows with uploaded files and existing configuration
     *
     * @param array $rows
     * @param array $uploadedFiles
     * @param array $existingConfig
     * @return array
     */
    private function processRows(array $rows, array $uploadedFiles, array $existingConfig): array
    {
        foreach ($rows as $id => &$data) {
            if (isset($uploadedFiles[$id])) {
                // New file uploaded
                $data['image'] = $uploadedFiles[$id][$id];
            } elseif (!isset($existingConfig[$id])) {
                // Row doesn't exist in config and no new file
                unset($rows[$id]);
            } elseif (isset($data['image']) && is_array($data['image'])) {
                // Existing image, preserve from config
                $data = $this->preserveExistingImage($data, $existingConfig[$id]);
            }
        }
        
        return $rows;
    }
    
    /**
     * Preserve existing image data
     *
     * @param array $row
     * @param array $configData
     * @return array
     */
    private function preserveExistingImage(array $row, array $configData): array
    {
        if (isset($row['image']) && is_array($row['image'])) {
            $row['image'] = $configData['image'];
        }
        return $row;
    }

    /**
     * Get upload path
     *
     * @return string
     */
    private function getPath(): string
    {
        return self::UPLOAD_DIR;
    }
}