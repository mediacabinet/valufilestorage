<?php
namespace ValuFileStorage\Service\File;

use Zend\Stdlib\AbstractOptions;

class LocalFileOptions extends FileOptions{
    
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