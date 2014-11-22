<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console\Filter;

use Zend\Filter\FilterInterface;

class Json implements FilterInterface
{
    /**
     * @see \Zend\Filter\FilterInterface::filter()
     * @param mixed $value
     * @return mixed $value Returns the results of deserializing a JSON string;
     *     if incapable, returns the original value.
     */
    public function filter($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $data = json_decode($value, true);

        if (null === $data) {
            $this->reportJsonDeserializationError();
            return $value;
        }

        return $data;
    }

    /**
     * Raise a user warning if a JSON deserialization error occurred
     */
    protected function reportJsonDeserializationError()
    {
        $error = json_last_error();
        switch ($error) {
            case JSON_ERROR_NONE:
                return;
            default:
                trigger_error(
                    sprintf('Error deserializing JSON (%d)', $error),
                    E_USER_WARNING
                );
                break;
        }
    }
}
