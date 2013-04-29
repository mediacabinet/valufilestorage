<?php
namespace ValuFileStorage\Service;

use Zend\Stdlib\AbstractOptions;

class LocalFileServiceOptions extends FileServiceOptions{
    
    /**
     * Path variables
     * 
     * @var array
     */
    protected $paths = array();
    
	/**
     * @return the $paths
     */
    public function getPaths()
    {
        return $this->paths;
    }

	/**
     * @param array $paths
     */
    public function setPaths(array $paths)
    {
        $this->paths = $paths;
    }
}