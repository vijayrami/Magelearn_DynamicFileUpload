<?php
declare(strict_types=1);

namespace Magelearn\DynamicFileUpload\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\Uploader;
use Psr\Log\LoggerInterface;

class ImageUploader
{
    private const MAX_FILE_SIZE = 2048000; // 2MB limit
    private const ALLOWED_MIME_TYPES = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'png'  => 'image/png',
        'webp' => 'image/webp',
    ];
    
    /**
     * @var ArrayFileModifier
     */
    private $arrayFileModifier;
    
    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    private $uploaderFactory;
    
    /**
     * @var string
     */
    private $uploadDir;
    
    /**
     * @var Filesystem
     */
    protected $filesystem;
    
    /**
     * @var LoggerInterface
     */
    protected $logger;
    
    /**
     * @var array
     */
    private $allowExtensions;
    
    /**
     * @param ArrayFileModifier $arrayFileModifier
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param string $uploadDir
     * @param array $allowExtensions
     */
    public function __construct(
        ArrayFileModifier $arrayFileModifier,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        LoggerInterface $logger,
        string $uploadDir = 'magelearn/dynamicfileupload',
        array $allowExtensions = ['jpg', 'jpeg', 'gif', 'png', 'webp']
        ) {
            $this->arrayFileModifier = $arrayFileModifier;
            $this->uploaderFactory = $uploaderFactory;
            $this->filesystem = $filesystem;
            $this->logger = $logger;
            $this->uploadDir = $uploadDir;
            $this->allowExtensions = array_intersect(array_keys(self::ALLOWED_MIME_TYPES), $allowExtensions);
    }
    
    /**
     * Upload files
     *
     * @return array
     * @throws LocalizedException
     */
    public function upload(): array
    {
        try {
            $files = $this->arrayFileModifier->modify();
            if (empty($files)) {
                return [];
            }
            
            return $this->processFiles($files);
        } catch (\Exception $e) {
            $this->logger->critical('File upload error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw new LocalizedException(__('Error during file upload. Please try again.'));
        }
    }
    
    /**
     * Process uploaded files
     *
     * @param array $files
     * @return array
     * @throws LocalizedException
     */
    private function processFiles(array $files): array
    {
        $result = [];
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $destinationPath = $mediaDirectory->getAbsolutePath($this->uploadDir);
        
        foreach ($files as $id => $file) {
            try {
                if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
                    throw new LocalizedException(__('File does not exist'));
                }
                
                $this->validateFile($file);
                
                $uploader = $this->uploaderFactory->create(['fileId' => $id]);
                $uploader->setAllowedExtensions($this->allowExtensions);
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);
                $uploader->addValidateCallback('size', $this, 'validateMaxSize');
                
                $uploadResult = $uploader->save($destinationPath);
                
                if (!$uploadResult) {
                    throw new LocalizedException(__('File cannot be saved to path: %1', $destinationPath));
                }
                
                $result[$id] = $uploadResult;
                $result[$id][$id] = 'magelearn/dynamicfileupload' . '/' . ltrim($uploadResult['file'], '/');
                
            } catch (\Exception $e) {
                $this->logger->error(sprintf(
                    'Error uploading file %s: %s',
                    $id,
                    $e->getMessage()
                    ), ['trace' => $e->getTraceAsString()]);
                
                throw new LocalizedException(
                    __('Failed to upload file %1: %2', $id, $e->getMessage())
                    );
            }
        }
        
        return $result;
    }
    
    /**
     * Validate uploaded file
     *
     * @param array $file
     * @return void
     * @throws LocalizedException
     */
    private function validateFile(array $file): void
    {
        // Basic file validation
        if (!isset($file['tmp_name']) || !isset($file['name'])) {
            throw new LocalizedException(__('Invalid file data'));
        }
        
        // Size validation
        $this->validateMaxSize($file['tmp_name']);
        
        // MIME type validation
        $this->validateImageType($file['tmp_name']);
    }
    
    /**
     * Validate image type
     *
     * @param string $filePath
     * @return bool
     * @throws LocalizedException
     */
    private function validateImageType(string $filePath): bool
    {
        $mimeType = mime_content_type($filePath);
        
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new LocalizedException(
                __('Invalid image type. Allowed types: %1', implode(', ', $this->allowExtensions))
                );
        }
        
        return true;
    }
    
    /**
     * Validate max file size
     *
     * @param string $filePath
     * @return void
     * @throws LocalizedException
     */
    public function validateMaxSize(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new LocalizedException(__('File does not exist'));
        }
        
        if (filesize($filePath) > self::MAX_FILE_SIZE) {
            throw new LocalizedException(
                __('File size exceeds the maximum limit of %1MB.', self::MAX_FILE_SIZE / 1024 / 1024)
                );
        }
    }
}