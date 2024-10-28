<?php
declare(strict_types=1);

namespace Magelearn\DynamicFileUpload\Block\Adminhtml;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\UrlInterface;

class ImageButton extends \Magento\Backend\Block\Template
{
    protected $_template = 'Magelearn_DynamicFileUpload::config/array_serialize/image.phtml';
    
    /**
     * @var Filesystem
     */
    private $filesystem;
    
    /**
     * @var UrlInterface
     */
    private $url;

    private $mediaDirectory;

    private $assetRepository;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        Filesystem $filesystem,
        UrlInterface $url,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        array $data = []
    ) {
        $this->assetRepository = $assetRepository;
        $this->filesystem = $filesystem;
        $this->url = $url;
        parent::__construct($context, $data);
    }

    public function getAssertRepository(): \Magento\Framework\View\Asset\Repository
    {
        return $this->assetRepository;
    }
    
    /**
     * @return string
     */
    public function getImageUrl()
    {
        return $this->url->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]);
    }

    public function getMediaDirectory()
    {
        if ($this->mediaDirectory === null) {
            $this->mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        }
        
        return $this->mediaDirectory;
    }
}