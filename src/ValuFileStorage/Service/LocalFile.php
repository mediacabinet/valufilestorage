<?php
namespace ValuFileStorage\Service;

use Valu\Utils\UuidGenerator;
use ValuFileStorage\Service\Exception;
use ValuFileStorage\Model;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Local file storage implementation
 * 
 * @author juhasuni
 *
 */
class LocalFile extends AbstractFileService
{
    protected $optionsClass = 'ValuFileStorage\Service\File\LocalFileOptions';
    
    public static function version()
    {
        return '0.1';
    }
    
    /**
     * Add file to storage
     *
     * This method reads file contents from the URL specified.
     * Supported URL schemes are file, http and https.
     *
     * @param string $sourceUrl File URL
     * @param string $targetUrl Target URL
     * @param array $metadata File metadata
     * @throws \ValuFileStorage\Service\Exception\FileNotFoundException
     */
    public function insert($sourceUrl, $targetUrl, array $metadata = array())
    {
        $specs = $this->prepareInsert($sourceUrl, $targetUrl);
        $path  = $this->resolvePath($targetUrl);
        
        // Create a new file or overwrite existing
        if (is_dir($path)) {
            $file = $this->makeFile($sourceUrl, $path);
    
        } else {
            $file = $path;
        }
        
        if ($specs['file']) {
            copy($specs['file'], $file);
        } else {
            file_put_contents($file, $specs['bytes']);
        }
    
        return $this->fetchFileMetadata($file);
    }
    
    /**
     * Read file data
     *
     * @param string $url
     * @return string|boolean
     * @throws Exception\FileNotFoundException
     */
    public function read($url){
         
        $this->testUrl($url);
         
        $file = $this->getFileByUrl($url, true);
        return file_get_contents($file);
    }
    
    /**
     * Write data to file
     *
     * @param string $url
     * @param string $data
     * @return boolean True on success, false otherwise
     */
    public function write($url, $data){
         
        $this->testUrl($url);
         
        $file = $this->getFileByUrl($url, true);
        return file_put_contents($file, $data);
    }
    
    /**
     * Retrieve metadata for file
     *
     * @param string $url    File URL
     * @return array|null File info (url, filesize, mimeType)
     */
    public function getMetadata($url)
    {
        $this->testUrl($url);
         
        $file = $this->getFileByUrl($url, true);
        return $this->fetchFileMetadata($file);
    }
    
    /**
     * Retrieves the total file storage size in bytes
     *
     * @return int
     */
    public function totalSize($url = null)
    {
        if ($url !== null) {
            $this->testUrl($url);
            $path = $this->resolvePath($url);
        } else {
            $path = '/';
        }
        
        if ($path == '/') {
            $paths = $this->getOption('paths');
        } else {
            $paths = array($path);
        }
        
        $size = 0;
        
        foreach($paths as $path){
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path),
                \RecursiveIteratorIterator::CHILD_FIRST);
            
            foreach($iterator as $fileInfo){
                if ($fileInfo->isFile()) {
                    $size += $fileInfo->getSize();
                }
            }
        }
        
        return $size;
    }
    
    /**
     * Retrieve path in local file system
     *
     * @param string $url
     * @return string
     */
    public function getPath($url)
    {
        $this->testUrl($url);
        return $this->resolvePath($url);
    }
    
    /**
     * Retrieve local copy of the file
     *
     * @param string $url
     * @return string
     */
    public function getLocalCopy($url)
    {
        $this->testUrl($url);
         
        $file = $this->getFileByUrl($url);
        $tmpFile = tempnam(sys_get_temp_dir(), 'valu-temp');
        
        copy($file, $tmpFile);
         
        return $tmpFile;
    }
    
    /**
     * Delete file
     *
     * @param string $url   File URL in filesystem
     * @return boolean		True if file was found and removed
     */
    public function delete($url)
    {
        $this->testUrl($url);
         
        $file = $this->getFileByUrl($url, false);
        
        if ($file) {
            $dir = dirname($file);
            unlink($file);
            rmdir(dirname($file));
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Delete all files in file storage in specified
     * path
     *
     * @return int Number of files deleted
     */
    public function deleteAll($url)
    {
        $this->testUrl($url);
        
        if (preg_match('#^'.$this->getOption('url_scheme') . ':///?$#', $url)) {
            $path = '/';
        } else {
            $path = $this->resolvePath($url);
        }
        
        if ($path == '/') {
            $paths = $this->getOption('paths');
        } else {
            $paths = array($path);
        }
        
        // Iterate each directory recursively and remove
        // sub directories and files
        foreach ($paths as $dir) {
            
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir),
                \RecursiveIteratorIterator::CHILD_FIRST);
            
            foreach($iterator as $fileInfo){
                if ($fileInfo->isFile() && is_writable($fileInfo->getPathname())) {
                    unlink($fileInfo->getPathname());
                } elseif ($fileInfo->isDir() && is_writable($fileInfo->getPathname())) {
                    rmdir($fileInfo->getPathname());
                }
            }
        }
    }
    
    /**
     * Fetch file info
     *
     * @param string $file
     * @return array
     */
    protected function fetchFileMetadata($file){
        
        $finfo = new \finfo();
        
        $items = explode(DIRECTORY_SEPARATOR, $file);
        $name = array_pop($items);
        $uuid = array_pop($items);
        $path = implode(DIRECTORY_SEPARATOR, $items);
        $key  = array_search($path, $this->getOption('paths'));

        return array(
            'url'      => $this->getOption('url_scheme') . ':///$'.$key . '/' . $uuid . '/' . $name,
            'filesize' => filesize($file),
            'mimeType' => $finfo->file($file, FILEINFO_MIME_TYPE)
        );
    }
    
    protected function getFileByUrl($url, $throwException = false)
    {
        $file = $this->resolvePath($url);
        
        if (file_exists($file)) {
            return $file;
        } else {
            if ($throwException) {
                throw new Exception\FileNotFoundException(
                    'File %URL% not found', array('URL' => $url));
            }
            
            return null;
        }
    }
    
    /**
     * Resolve path in local file system for URL
     * 
     * @param string $url
     * @throws Exception\InvalidTargetUrlException
     * @return string
     */
    protected function resolvePath($url)
    {
        $paths = $this->getOption('paths');
        $path  = $this->parsePath($url);
        $items = explode('/', $path);
        
        if(sizeof($items) > 1 && substr($items[1], 0, 1) == '$'){
            array_shift($items);
            
            
            $var = ltrim($items[0], '$');
            
            if (array_key_exists($var, $paths)){
                $items[0] = $paths[$var];
            } else {
                throw new Exception\InvalidTargetUrlException(
                    'Unknown path variable $%VAR% for URL %URL%',
                    array('VAR' => $var, 'URL' => $url));
            }
        } else {
            $valid = false;
            
            foreach ($paths as $allowed) {
                if(strpos($path, $allowed) === 0) {
                    $valid = true;
                }
            }
            
            if (!$valid) {
                throw new Exception\PermissionDeniedException(
                    'Writing file to URL %URL% is not allowed',
                    array('URL' => $url));
            }
        }
        
        return implode('/', $items);
    }
    
    /**
     * Retrieve complete path (including filename) for new file
     * 
     * @param string $sourceUrl
     * @param string $path
     * @return string
     */
    protected function makeFile($sourceUrl, $path)
    {
        $id       = UuidGenerator::generate(UuidGenerator::VERSION_3, uniqid(), 'valu-file-storage');
        $basename = basename($this->parsePath($sourceUrl));
        $dir      = $path . '/' . $id;
        
        if (file_exists($dir)) {
            return $this->makeFile($sourceUrl, $path);
        } else {
            mkdir($dir);
        }
        
        return $dir . '/' .$basename;
    }
}