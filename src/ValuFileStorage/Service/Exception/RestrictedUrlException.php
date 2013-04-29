<?php
namespace ValuFileStorage\Service\Exception;

use ValuSo\Exception\ServiceException;

class RestrictedUrlException extends ServiceException
{
    protected $code = 2003;
}