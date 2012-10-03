<?php
namespace ValuFileStorage\Service\Exception;

use Valu\Service\Exception\SkippableException;

class UnsupportedUrlException extends SkippableException
{
    protected $code = 2005;
}