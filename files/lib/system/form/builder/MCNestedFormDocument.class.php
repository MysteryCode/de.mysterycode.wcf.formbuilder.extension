<?php

namespace wcf\system\form\builder;

class MCNestedFormDocument extends FormDocument
{
    /**
     * @inheritDoc
     */
    public function getRequestData($index = null)
    {
        if ($this->requestData === null) {
            $this->requestData = $_POST;
        }

        if ($index !== null && \strpos($index, '[') !== false) {
            \preg_match('/^([^\[]+)(\[.*])$/', $index, $matches);
            if (!empty($matches[2])) {
                unset($matches[0]);

                \preg_match_all('/\[([^]]+)]/', $matches[2], $parts);
                if (!empty($parts[1])) {
                    $i = 0;
                    $source = $this->requestData[$matches[1]];
                    while (!empty($parts[1][$i]) && \is_array($source)) {
                        $source = $source[$parts[1][$i]] ?? null;
                        $i++;
                    }

                    if ($source !== null) {
                        return $source;
                    }
                }
            }
        }

        return parent::getRequestData($index);
    }

    /**
     * @inheritDoc
     */
    public function hasRequestData($index = null): bool
    {
        $requestData = $this->getRequestData();

        if ($index !== null && \strpos($index, '[') !== false) {
            \preg_match('/^([^\[]+)(\[.*])$/', $index, $matches);
            if (!empty($matches[2])) {
                unset($matches[0]);

                \preg_match_all('/\[([^]]+)]/', $matches[2], $parts);
                if (!empty($parts[1])) {
                    $i = 0;
                    $source = $requestData[$matches[1]];
                    while (!empty($parts[1][$i]) && \is_array($source)) {
                        $source = $source[$parts[1][$i]] ?? null;
                        $i++;
                    }

                    return $source !== null;
                }
            }
        }

        return parent::hasRequestData($index);
    }
}
