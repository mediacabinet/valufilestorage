<?php
namespace ValuFileStorage\Service;

use ValuSo\Feature;
use ValuSo\Annotation as ValuService;
use Doctrine\ODM\MongoDB\Id\UuidGenerator;

/**
 * Local file storage implementation
 * 
 * @author juhasuni
 *
 * @ValuService\Context("native")
 */
abstract class AbstractFileService
    implements Feature\ConfigurableInterface
{
    use Feature\OptionsTrait;
    
	protected $optionsClass = 'ValuFileStorage\Service\FileServiceOptions';
	
	/**
     * @var \Doctrine\ODM\MongoDB\Id\UuidGenerator
	 */
	protected $uuidGenerator;

	/**
	 * Test whether or not a file exists
	 *
	 * @param string $url
	 * @return boolean
	 */
	public function exists($url)
	{
	    $this->testUrl($url);
	
	    $file = $this->getFileByUrl($url, false);
	    return $file !== null;
	}
	
	/**
     * Prepare file insertion
     * 
     * @param string $sourceUrl
     * @param string $targetUrl
     * @return array
	 */
	protected function prepareInsert($sourceUrl, $targetUrl)
	{
	    $this->testUrl($targetUrl);
	    
	    $sourceUrl	= $this->canonicalUrl($sourceUrl);
	    $path       = $this->parsePath($sourceUrl);
	    $scheme		= $this->parseScheme($sourceUrl);
	    $bytes      = null;
	    $file		= null;
	    
	    if (!in_array($scheme, array('file', 'http', 'https'))) {
	        throw new Exception\UnsupportedUrlException(
                'Unsupported source URL scheme:%SCHEME%',
                array('SCHEME' => $scheme));
	    }
	    
	    $source = null;
	    $bytes  = null;
	    
	    if (!$this->isSafeUrl($sourceUrl)) {
	        throw new Exception\RestrictedUrlException(
	                sprintf('Reading files is not allowed from URL %s',$sourceUrl));
	    }
	    
	    if ($scheme == 'file') {
	        $file = trim($path);
	    
	        if(!file_exists($file)){
	            throw new Exception\FileNotFoundException(
	                    'Local file %FILE% not found',
	                    array('FILE' => $file));
	        }
	    } elseif (($bytes = file_get_contents($sourceUrl)) === false) {
	        throw new Exception\FileNotFoundException(
	                'Remote URL %URL% could not be read',
	                array('URL' => $sourceUrl));
	    }
	    
	    return array(
	        'file' => $file,
            'bytes' => $bytes        
        );
	}
	
	/**
	 * Retrieve instance of file by URL
	 * 
	 * @param string $url
	 * @param boolean $throwException
	 */
	protected abstract function getFileByUrl($url, $throwException = false);
	
	/**
	 * Retrieve URL's canonical form
	 * 
	 * @param string $url
	 * @return string
	 */
	protected function canonicalUrl($url)
	{
	    $url = trim($url);
	    
	    // Assume local URL if scheme is missing
	    if (strpos($url, '://') === false) {
	        $url = 'file://' . $url;
	    }
	    
	    if (strpos($url, 'file://') === 0) {
	        $path = $this->parsePath($url);
	        $path = realpath($path);
	        
	        $url = 'file://' . $path;
	    }
	    
	    return $url;
	}
	
	/**
	 * Is URL safe for reading?
	 * 
	 * @param string $url
	 * @return boolean
	 */
	protected function isSafeUrl($url){
		return 	$this->isWhitelisted($url) &&
				!$this->isBlacklisted($url);
	}
	
	/**
	 * Is URL whitelisted?
	 * 
	 * @param string $url
	 * @return boolean
	 */
	protected function isWhitelisted($url){
		return $this->matchesArray($url, $this->getOption('whitelist'));
	}
	
	/**
	 * Is URL blacklisted?
	 * 
	 * @param string $url
	 * @return boolean
	 */
	protected function isBlacklisted($url){
		return $this->matchesArray($url, $this->getOption('blacklist'));
	}
	
	/**
	 * Match given URL against array of regular expressions and
	 * return true if a match is found
	 * 
	 * @param string $url
	 * @param array $regexps
	 * @return boolean
	 */
	protected function matchesArray($url, $regexps){
		if(sizeof($regexps)){
			foreach ($regexps as $regexp){
				$re = '#' . $regexp . '#i';
				
				if(preg_match($re, $url)){
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Is the URL scheme supported?
	 *
	 * @param string $url
	 * @return boolean
	 */
	protected function isSupportedScheme($url){
	    return strpos($url, $this->getOption('url_scheme').'://') === 0;
	}
	
	/**
	 * Test that URL (scheme) is supported and throw exception
	 * if not
	 *
	 * @param string $url
	 * @throws Exception\UnsupportedUrlException
	 * @return boolean
	 */
	protected function testUrl($url)
	{
	    if ($this->isSupportedScheme($url)) {
	        return true;
	    } else {
	        throw new Exception\UnsupportedUrlException(
                'URL %URL% is not supported',
                array('URL' => $url));
	    }
	}
	
	protected function parseScheme($url)
	{
	    if (($scheme = parse_url($url, PHP_URL_SCHEME)) != false) {
	        return $scheme;
	    } elseif(strpos($url, '://') === false) {
	        return 'file';
	    } else {
	        return substr($url, 0, strpos($url, '://'));
	    }
	}
	
	/**
	 * Parse file path from URL
	 *
	 * @param string $url
	 * @return string
	 */
	protected function parsePath($url)
	{
	    if (($path = parse_url($url, PHP_URL_PATH)) != false) {
	        return $path;
	    } else {
	        $scheme = $this->parseScheme($url);
	        $prefix = $scheme . '://';
	        
	        $url = $prefix . 'localhost' . substr($url, strlen($prefix));
	        return parse_url($url, PHP_URL_PATH);
	    }
	}
	
	/**
     * Generate new UUID token
     * 
     * @return string
	 */
	protected function generateUuid()
	{
	    if (!$this->uuidGenerator) {
	        $this->uuidGenerator = new UuidGenerator();
	    }
	    
	    return $this->uuidGenerator->generateV5($this->uuidGenerator->generateV4(), php_uname('n'));
	}
}