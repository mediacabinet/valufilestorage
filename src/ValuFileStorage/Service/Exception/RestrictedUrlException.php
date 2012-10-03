<?php
namespace ValuFileStorage\Service\Exception;

use Valu\Service\Exception\ServiceException;

class RestrictedUrlException extends ServiceException
{
    protected $code = 2003;
}