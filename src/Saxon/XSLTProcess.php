<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Saxon;

use Symphony\Symphony\AbstractXSLTProcessor;
use Saxon\SaxonProcessor;

final class XSLTProcess extends AbstractXSLTProcessor
{
    public static function isXSLTProcessorAvailable(): bool
    {
        return class_exists('\\Saxon\\SaxonProcessor');
    }

    public function validate(string $xsd, ?string $xml = null): bool
    {
        // TODO
        return true;
    }

    public function process(?string $xml = null, ?string $xsl = null, array $parameters = [], array $registerFunctions = [])
    {
        if (false == empty($registerFunctions)) {
            throw new Exceptions\SaxonExceptionException('Registering of callbacks is not currently supported when using Saxon extension');
        }

        if (false == self::isXSLTProcessorAvailable()) {
            throw new Exceptions\SaxonNotInstalledException();
        }

        parent::process($xml, $xsl, $parameters, $registerFunctions);

        $method = 'processProductionMode'.(true == \Extension_Saxon::isProductionModeEnabled()
            ? 'Enabled'
            : 'Disabled')
        ;

        return $this->$method($xml, $xsl, $parameters, $registerFunctions);
    }

    private function processProductionModeEnabled(?string $xml = null, ?string $xsl = null, array $parameters = [], array $registerFunctions = []): ?string
    {
        $saxonProc = new SaxonProcessor();

        $xsltProc = $saxonProc->newXsltProcessor();

        if(false == empty($parameters)) {
            foreach($parameters as $key => $value) {
                if(false == is_array($value)) {
                    $xsltProc->setParameter(
                        $key,
                        $saxonProc->createAtomicValue($value)
                    );
                } else {
                    // Note: the follow code causes JET runtime to segfault
                    // and crash. It is unclear why exactly, and the
                    // Saxon/C PHP documentation is poor at best. This *SHOULD*
                    // be the way to create a parameter that contains multiple
                    // values (i.e. an array). Right now, this is a limitation
                    // and developers will need to use the /data/params XML
                    // instead of runtime parameters.
                    /*
                        $node = new SaxonXdmValue;
                        foreach($value as $v) {
                            $node->addXdmItem($saxonProc->createAtomicValue($v));
                        }
                    */
                }
            }
        }

        $xsltProc->compileFromString($this->xsl());

        $xmlNode = $saxonProc->parseXmlFromString($this->xml());
        if (null == $xmlNode) {
            $this->appendError(
                null,
                'Invalid XML. Unable to parse from string. Returned: '.$xsltProc->getErrorMessage(0),
                null, //file
                null, //line
                self::TYPE_XML  //type
            );

            return null;
        }

        $xsltProc->setSourceFromXdmValue($xmlNode);

        $result = $xsltProc->transformToString();

        $this->appendSaxonErrors($xsltProc);

        return $result;
    }

    private function processProductionModeDisabled(?string $xml = null, ?string $xsl = null, array $parameters = [], array $registerFunctions = []): ?string
    {
        // Running Saxon from within Apache means we lose pretty much all
        // meaningful error messages. The only way around this is to use
        // a exec() to run the code on the shell, which gives us access
        // to errors messages sent to STDERR. The downside is it's about
        // 100x slower.
        $tmpfname = tempnam('/tmp', 'SaxonXSLT3');
        file_put_contents($tmpfname, '#!/usr/bin/env php
<?php declare(strict_types=1);
use Saxon\SaxonProcessor;
$success = true;

$saxonProc = new SaxonProcessor;
$xsltProc = $saxonProc->newXsltProcessor();

$parameters = json_decode(\''.json_encode($parameters).'\');

if(false == empty($parameters)) {
    foreach($parameters as $key => $value) {
        if(false == is_array($value)) {
            $xsltProc->setParameter(
                $key,
                $saxonProc->createAtomicValue($value)
            );
        } else {
            // Note: the follow code causes JET runtime to segfault
            // and crash. It is unclear why exactly, and the
            // Saxon/C PHP documentation is poor at best. This *SHOULD*
            // be the way to create a parameter that contains multiple
            // values (i.e. an array). Right now, this is a limitation
            // and developers will need to use the /data/params XML
            // instead of runtime parameters.
            /*
                $node = new SaxonXdmValue;
                foreach($value as $v) {
                    $node->addXdmItem($saxonProc->createAtomicValue($v));
                }
            */
        }
    }
}

$xsltProc->compileFromString(\''.$xsl.'\');

$xmlNode = $saxonProc->parseXmlFromString(\''.$xml.'\');
if(null == $xmlNode) {
    printf(
        "Invalid XML. Unable to parse from string. Returned: %s" . PHP_EOL,
        $xsltProc->getErrorMessage(0)
    );
    $success = false;
}

if(true == $success) {
    $xsltProc->setSourceFromXdmValue($xmlNode);
    $result = $xsltProc->transformToString();
    if($xsltProc->getExceptionCount() > 0) {
        for($ii = 0; $ii < $xsltProc->getExceptionCount(); $ii++) {
            printf(
                "Unexpected error: Code %s; Message %s" . PHP_EOL,
                $xsltProc->getErrorCode($ii),
                $xsltProc->getErrorMessage($ii)
            );
        }
        $xsltProc->exceptionClear();
        $success = false;

    } else {
        echo $result;
    }
}

// Cleanup
$xsltProc->clearParameters();
$xsltProc->clearProperties();
unset($xsltProc);
unset($xdmNode);
unset($saxonProc);

// Send the correct status code
exit(true == $success ? 0 : 1);
        ');

        exec('php -f '.$tmpfname.' 2>&1', $output, $status);
        $output = trim(implode(PHP_EOL, $output));

        // Check the status. 0 = success, 1 = failure
        if (0 == $status) {
            return $output;
        }

        // Transformation failed. Process errors here
        $this->appendSaxonError($output);

        return null;
    }

    private function appendSaxonErrors(\Saxon\XSLTProcessor $proc, ?string $type = null): void
    {
        if ($proc->getExceptionCount() <= 0) {
            return;
        }

        for ($ii = 0; $ii < $proc->getExceptionCount(); ++$ii) {
            $this->appendSaxonError($proc->getErrorMessage($ii));
        }

        $proc->exceptionClear();
    }

    private function appendSaxonError(string $message): void
    {
        $parts = explode(';', $message);

        $parts = array_map(function ($val) {
            $val = trim($val);

            if (false == preg_match_all(
                "@^(systemId|lineNumber|columnNumber):\s+(.+)@i",
                $val,
                $matches
            )) {
                return $val;
            }

            $name = $matches[1][0];
            $value = $matches[2][0];

            $return = [
                'name' => $name,
                'value' => (
                    'systemId' == $name
                    ? ltrim(urldecode($value), 'file:')
                    : (int) $value
                ),
            ];

            return $return;
        }, $parts);

        $line = null;
        $file = null;
        $column = null;
        $message = null;

        foreach ($parts as $p) {
            if (false == is_array($p)) {
                $message .= " - {$p}";
                continue;
            }

            switch ($p['name']) {
                // Will be the file with the error
                case 'systemId':
                    $file = $p['value'];
                    break;

                case 'lineNumber':
                    $line = $p['value'];
                    break;

                case 'columnNumber':
                    $column = $p['value'];
                    break;
            }
        }

        $this->appendError(
            null,
            $message,
            $file, //file
            $line.(null != $column ? " Col: {$column}" : null), //line
            $type //type
        );
    }
}
