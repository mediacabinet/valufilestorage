<?php
namespace ValuFileStorage\Service;

use ValuFileStorage\Service\Exception;

/**
 * Local file storage implementation
 *
 * @author juhasuni
 *
 */
class LocalFileService extends AbstractFileService
{
    protected $optionsClass = 'ValuFileStorage\Service\LocalFileServiceOptions';

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
     *
     * @ValuService\Context({"cli", "native"})
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
     *
     * @ValuService\Context({"cli", "native"})
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
     *
     * @ValuService\Context({"cli", "native"})
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
     * @throws \ValuFileStorage\Service\Exception\FileNotFoundException
     *
     * @ValuService\Context({"cli", "native"})
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
     *
     * @ValuService\Context({"cli", "native"})
     */
    public function totalSize($url = null)
    {
        if ($url !== null) {
            $this->testUrl($url);
            $path = $this->resolvePath($url, true);
        } else {
            $path = '/';
        }

        if ($path === '/') {
            $paths = $this->getOption('paths');
        } else {
            $paths = array($path);
        }

        $size = 0;
        $paths = array_unique($paths);

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
     *
     * @ValuService\Context({"cli", "native"})
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
     *
     * @ValuService\Context({"cli", "native"})
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
     * Retrieve a resource to be used with PHP's file
     * functions that deal with reading files
     *
     * @return resource
     *
     * @ValuService\Context({"native"})
     */
    public function getResource($url)
    {
        $this->testUrl($url);
        $file = $this->getFileByUrl($url);
        return fopen($file, 'r');
    }

    /**
     * Delete file
     *
     * @param string $url   File URL in filesystem
     * @return boolean		True if file was found and removed
     *
     * @ValuService\Context({"cli", "native"})
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
     *
     * @ValuService\Context({"cli", "native"})
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

        $paths = array_unique($paths);

        // Iterate each directory recursively and remove
        // sub directories and files
        foreach ($paths as $dir) {

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir),
                \RecursiveIteratorIterator::CHILD_FIRST);

            foreach($iterator as $fileInfo){

                if ($fileInfo->getFileName() === '.' || $fileInfo->getFileName() === '..') {
                    continue;
                }

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

        $items = explode(DIRECTORY_SEPARATOR, $file);
        $name = array_pop($items);
        $relativePath = [];
        $path = "";
        $pathKey = false;

        // pop items until we reach path that points to
        // a valid storage path
        while (sizeof($items)) {
            array_unshift($relativePath, array_pop($items));

            $path = implode(DIRECTORY_SEPARATOR, $items);
            $pathKey = array_search($path, $this->getOption('paths'));

            if ($pathKey !== false) {
                break;
            }
        }

        // if path doesn't point to a valid storage path,
        // return null
        if ($pathKey === false || $path === "") {
            return null;
        }

        $cDate = new \DateTime();
        $cDate->setTimestamp(filectime($file));

        $mDate = new \DateTime();
        $mDate->setTimestamp(filemtime($file));

        if (preg_match('/^data\.([a-z]+\_.+)/', basename($file), $matches)) {
            $mimeType = str_replace('_', '/', $matches[1]);
        } else {
            $finfo = new \finfo();
            $mimeType = $finfo->file($file, FILEINFO_MIME_TYPE);
        }

        return array(
            'url'          => $this->getOption('url_scheme') . ':///$' . $pathKey . '/' . implode('/', $relativePath) . '/' . $name,
            'filesize'     => filesize($file),
            'mimeType'     => $mimeType,
            'createdAt'    => $cDate->format(DATE_ATOM),
            'modifiedAt'   => $mDate->format(DATE_ATOM),
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
     * @param boolean $allowRoot
     * @throws Exception\InvalidTargetUrlException
     * @return string
     */
    protected function resolvePath($url, $allowRoot = false)
    {
        $paths = $this->getOption('paths');
        $path  = $this->parsePath($url);
        $items = explode('/', $path);

        if(sizeof($items) > 1 && substr($items[1], 0, 1) == '$'){
            array_shift($items);

            $var = ltrim($items[0], '$');

            if (array_key_exists($var, $paths)){
                $items[0] = $paths[$var];

                // Try to make dir if it doesn't exist
                if (!is_dir($items[0])) {

                    if (!mkdir($items[0], 0777, true)) {
                        throw new Exception\TargetPathNotWritableException(
                            'Target path for %VAR% is not writable', array('VAR' => $var));
                    }
                }

            } else {
                throw new Exception\InvalidTargetUrlException(
                    'Unknown path variable $%VAR% for URL %URL%',
                    array('VAR' => $var, 'URL' => $url));
            }
        } else {

            if ($allowRoot && $path === '/') {
                return $path;
            }

            $valid = false;

            $paths = array_unique($paths);
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
        $id       = $this->generateUuid();
        $dir      = $path;
        $basename = $this->parseBasename($sourceUrl);
        $intermediate = $this->getOption('hashed_dir_levels');
        $intermediate = min($intermediate, (strlen($id) / 2));

        for ($i = 0; $i < $intermediate; $i++) {
            $dir .= DIRECTORY_SEPARATOR . substr($id, ($i * 2), 2);
        }

        $dir .= DIRECTORY_SEPARATOR . $id;

        if (file_exists($dir)) {
            return $this->makeFile($sourceUrl, $path);
        } else {
            mkdir($dir, 0755, true);
        }

        return $dir . DIRECTORY_SEPARATOR . $basename;
    }
}
