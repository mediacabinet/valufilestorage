<?php
namespace ValuFileStorage\Service\Exception;

use Valu\Service\Exception\ServiceException;

class FileNotFoundException extends ServiceException
{
    protected $code = 2001;        
}