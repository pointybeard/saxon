<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Saxon\Exceptions;

class IncorrectSaxonVersionInstalledException extends SaxonExceptionException
{
    public function __construct(string $version, $code = 0, \Exception $previous = null)
    {
        return parent::__construct('Saxon/C is installed, however, it does not appear to be the correct version. Only version 1.1.2 is supported. Detected version: . '.$version, $code, $previous);
    }
}
