<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console\Filter;

use Zend\Filter\FilterInterface;

class Explode implements FilterInterface
{
    /**
     * Default delimiter to use if none provided, or invalid specification provided to constructor.
     *
     * @var string
     */
    protected $defaultDelimiter = ',';

    /**
     * Delimiter to use when filtering values
     *
     * @var string
     */
    protected $delimiter;

    /**
     * @param null|array|string $delimiter Delimiter to use when exploding a string to an array
     */
    public function __construct($delimiter = null)
    {
        if (is_array($delimiter)) {
            if (isset($delimiter['delimiter'])) {
                $delimiter = $delimiter['delimiter'];
            } elseif (count($delimiter)) {
                $delimiter = array_shift($delimiter);
            } else {
                $delimiter = $this->defaultDelimiter;
            }
        }

        if (null === $delimiter
            || ! is_string($delimiter)
        ) {
            $delimiter = $this->defaultDelimiter;
        }

        $this->delimiter = $delimiter;
    }

    /**
     * @see \Zend\Filter\FilterInterface::filter()
     * @param mixed $value
     * @return array|mixed $value Returns an array if a string $value was provided
     */
    public function filter($value)
    {
        if (! is_string($value)) {
            return $value;
        }

        return explode($this->delimiter, $value);
    }
}
