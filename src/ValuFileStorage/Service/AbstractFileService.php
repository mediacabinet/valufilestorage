<?php
namespace ValuFileStorage\Service;

use Valu\Service\AbstractService;

/**
 * Local file storage implementation
 * 
 * @author juhasuni
 *
 */
abstract class AbstractFileService extends AbstractService
{
	protected $optionsClass = 'ValuFileStorage\Service\File\FileOptions';
	
	protected function prepareInsert($sourceUrl, $targetUrl)
	{
	    $this->testUrl($targetUrl);
	    
	    $sourceUrl	= trim($sourceUrl);
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
}