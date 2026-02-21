<?php

if (!\file_exists('option.xml')) {
    echo "option.xml not found.";

    exit(0);
}

$xmlString = \file_get_contents('option.xml');

try {
    $xml = new SimpleXMLElement($xmlString);
} catch (Exception $e) {
    exit(0);
}

$namespaces = $xml->getNamespaces(true);
$xml->registerXPathNamespace('ns', $namespaces['']);

$constants = [];
$constantNames = [];

foreach ($xml->xpath('//ns:import/ns:options/ns:option') as $option) {
    $name = \strtoupper(\str_replace(['.', ':'], '_', (string)$option['name']));
    $defaultValue = (string)$option->defaultvalue;
    $optionType = (string)$option->optiontype;
    $constantNames[] = $name;

    if ($defaultValue === '') {
        $constants[] = "const $name = '';";
    } elseif ($optionType === 'boolean' || $optionType === 'integer') {
        $constants[] = "const $name = " . (int)$defaultValue . ";";
    } else {
        $constants[] = "const $name = '$defaultValue';";
    }
}

\file_put_contents('constants.php', "<?php\n\n" . \implode("\n", $constants) . "\n");

$phpstanPath = 'phpstan.neon.dist';
if (!empty($constantNames) && \file_exists($phpstanPath)) {
    $phpstan = \file_get_contents($phpstanPath);

    if ($phpstan === false) {
        exit(0);
    }

    $dynamicConstantNames = \array_values(\array_unique($constantNames));

    $dynamicBlock = "    dynamicConstantNames:\n";

    foreach ($dynamicConstantNames as $name) {
        $dynamicBlock .= "        - {$name}\n";
    }

    $pattern = '/^\s*dynamicConstantNames:\s*\n(?:\s*-\s*.*\n)*/m';

    if (\preg_match($pattern, $phpstan)) {
        $phpstan = \preg_replace($pattern, $dynamicBlock, $phpstan);
    } else {
        $lines = \preg_split("/\r\n|\n|\r/", $phpstan);
        $insertAfter = null;

        foreach ($lines as $index => $line) {
            if (\preg_match('/^\s*level:\s*/', $line)) {
                $insertAfter = $index;
                break;
            }
        }

        if ($insertAfter === null) {
            foreach ($lines as $index => $line) {
                if (\trim($line) === 'parameters:') {
                    $insertAfter = $index;
                    break;
                }
            }
        }

        $blockLines = \explode("\n", \rtrim($dynamicBlock, "\n"));

        if ($insertAfter === null) {
            $lines = \array_merge($blockLines, $lines);
        } else {
            \array_splice($lines, $insertAfter + 1, 0, $blockLines);
        }

        $phpstan = \implode("\n", $lines);
    }

    \file_put_contents($phpstanPath, \rtrim($phpstan) . "\n");
}

echo "constants.php has been generated successfully.";
