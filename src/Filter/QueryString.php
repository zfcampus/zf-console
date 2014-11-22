<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console\Filter;

use Zend\Filter\FilterInterface;
use Zend\Stdlib\ArrayUtils;

class QueryString implements FilterInterface
{
    /**
     * Internal filter used to deserialize JSON-formatted query string values
     *
     * @var Json
     */
    protected $jsonFilter;

    public function __construct()
    {
        $this->jsonFilter = new Json();
    }

    /**
     * @see \Zend\Filter\FilterInterface::filter()
     * @param mixed $value
     * @return mixed Original value, if not a string, or an array of key/value pairs
     */
    public function filter($value)
    {
        if (! is_string($value)) {
            return $value;
        }

        // check if the value provided resembles a query string
        $pairs = explode('&', $value);
        foreach ($pairs as $pair) {
            list($k, $v) = explode('=', $pair);

            // Check if we have a normal key-value pair
            if (! preg_match("/^(.*?)((\[(.*?)\])+)$/m", $k, $m)) {
                $data[$k] = $v;
                continue;
            }

            // Array values
            $parts = explode('][', rtrim(ltrim($m[2], '['), ']'));
            $json  = '{"'
                . implode('":{"', $parts)
                . '": '
                . json_encode($v)
                . str_pad('', count($parts), '}');

            if (isset($data[$m[1]])) {
                $data[$m[1]] = ArrayUtils::merge($data[$m[1]], $this->jsonFilter->filter($json));
                continue;
            }

            $data[$m[1]] = $this->jsonFilter->filter($json);
        }

        return $data;
    }
}
