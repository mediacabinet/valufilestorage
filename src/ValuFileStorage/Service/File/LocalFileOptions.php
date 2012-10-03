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
        foreach ($paths as $key => $path) {
            if (!is_dir($path) || !is_writable($path)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid path configuration for key "%s"; path %s is not writable',
                    $key, $path));
            }
            
            $paths[$key] = realpath($path);
        }
        
        $this->paths = $paths;
    }
}