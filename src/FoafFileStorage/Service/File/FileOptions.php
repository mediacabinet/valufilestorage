<?php
namespace FoafFileStorage\Service\File;

use Zend\Stdlib\AbstractOptions;

class FileOptions extends AbstractOptions{
    
    /**
     * Array of whitelist patterns
     * 
     * @var array
     */
    protected $whitelist = array();
    
    /**
     * Array of blacklist patterns
     * 
     * @var array
     */
    protected $blacklist = array();
    
    /**
     * URL scheme
     * 
     * @var string
     */
    protected $urlScheme = 'mongofs';
    
    /**
     * Batch size for delete operations
     * 
     * @var int
     */
    protected $deleteBatchSize = 100;
    
	/**
	 * @return array
	 */
	public function getWhitelist() {
		return $this->whitelist;
	}

	/**
	 * @return array
	 */
	public function getBlacklist() {
		return $this->blacklist;
	}

	/**
	 * @param array
	 */
	public function setWhitelist(array $whitelist) {
		$this->whitelist = $whitelist;
	}

	/**
	 * @param array
	 */
	public function setBlacklist(array $blacklist) {
		$this->blacklist = $blacklist;
	}
	
	/**
	 * Retrieve URL scheme
	 * 
	 * @return string
	 */
	public function getUrlScheme()
    {
        return $this->urlScheme;
    }

    /**
     * Set URL scheme
     * 
     * @param string $urlScheme
     */
	public function setUrlScheme($urlScheme)
    {
        $this->urlScheme = $urlScheme;
    }
    
    /**
     * Retrieve delete batch size
     * 
     * @return number
     */
	public function getDeleteBatchSize()
    {
        return $this->deleteBatchSize;
    }

    /**
     * Set delete batch size
     * @param unknown_type $deleteBatchSize
     */
	public function setDeleteBatchSize($deleteBatchSize)
    {
        $this->deleteBatchSize = intval($deleteBatchSize);
        
        if($this->deleteBatchSize <= 0){
            $this->deleteBatchSize = 0;
        }
    }
}