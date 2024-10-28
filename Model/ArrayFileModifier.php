<?php
declare(strict_types=1);

namespace Magelearn\DynamicFileUpload\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;

class ArrayFileModifier
{
    /**
     * @var RequestInterface
     */
    private $request;
    
    /**
     * @var string
     */
    private $requestName;
    
    /**
     * @param RequestInterface $request
     * @param string $requestName
     */
    public function __construct(
        RequestInterface $request,
        string $requestName = 'groups'
        ) {
            $this->request = $request;
            $this->requestName = $requestName;
    }
    
    /**
     * Modify file array structure
     *
     * @return array
     * @throws LocalizedException
     */
    public function modify(): array
    {
        try {
            $requestFiles = $this->parseRequest(
                $this->request->getFiles($this->requestName)
                );
            
            if (empty($requestFiles)) {
                return [];
            }
            
            return $this->processRequestFiles($requestFiles);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Error processing file upload request: %1', $e->getMessage()));
        }
    }
    
    /**
     * Process request files
     *
     * @param array $requestFiles
     * @return array
     */
    private function processRequestFiles(array $requestFiles): array
    {
        $files = [];
        foreach ($requestFiles as $id => $file) {
            $data = array_shift($file);
            if (!isset($data['tmp_name']) || empty($data['tmp_name'])) {
                continue;
            }
            $files[$id] = $data;
        }
        
        $_FILES = $files;
        return $files;
    }
    
    /**
     * Parse request data
     *
     * @param array|null $requestFiles
     * @return array
     */
    private function parseRequest(?array $requestFiles): array
    {
        if ($requestFiles === null) {
            return [];
        }
        
        if (isset($requestFiles['value'])) {
            return $requestFiles['value'];
        }
        
        if (is_array($requestFiles)) {
            $firstElement = array_shift($requestFiles);
            if (is_array($firstElement)) {
                return $this->parseRequest($firstElement);
            }
        }
        
        return $requestFiles;
    }
}