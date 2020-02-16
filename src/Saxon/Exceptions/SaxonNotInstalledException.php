<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Saxon\Exceptions;

class SaxonNotInstalledException extends SaxonExceptionException
{
    public function __construct($code = 0, \Exception $previous = null)
    {
        return parent::__construct('Saxon/C does not appear to be installed. Check README for instructions.', $code, $previous);
    }
}
