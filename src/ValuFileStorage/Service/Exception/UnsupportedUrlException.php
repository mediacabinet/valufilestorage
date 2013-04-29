<?php
namespace ValuFileStorage\Service\Exception;

use ValuSo\Exception\SkippableException;

class UnsupportedUrlException extends SkippableException
{
    protected $code = 2005;
}