<?php
namespace ValuFileStorage\Service\Exception;

use Valu\Service\Exception\SkippableException;

class UnsupportedOperationException extends SkippableException
{
    protected $code = 2004;
}