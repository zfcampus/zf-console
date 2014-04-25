<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Console;

use Zend\Console\Adapter\AdapterInterface as Console;

class HelpCommand
{
    /**
     * @var string|callable
     */
    protected $banner;

    /**
     * @var string|callable
     */
    protected $footer;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @param Router $router 
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param string|callable $bannerOrCallback 
     * @return self
     */
    public function setBanner($bannerOrCallback)
    {
        if (! is_string($bannerOrCallback) && ! is_callable($bannerOrCallback)) {
            throw new InvalidArgumentException('Help banner must be a string or callback');
        }

        $this->banner = $bannerOrCallback;
        return $this;
    }

    public function setFooter($footerOrCallback)
    {
        if (! is_string($footerOrCallback) && ! is_callable($footerOrCallback)) {
            throw new InvalidArgumentException('Help footer must be a string or callback');
        }

        $this->footer = $footerOrCallback;
        return $this;
    }

    /**
     * @param Route $route 
     * @param Console $console
     * @return int
     */
    public function __invoke(Route $route, Console $console)
    {
        $command = $route->getMatchedParam('command', null);

        $this->showMessage($this->banner, $console);
        $this->router->showUsageMessage($command);
        $this->showMessage($this->footer, $console);
        return 0;
    }

    /**
     * Display a message (banner or footer)
     *
     * If the message is a string and not callable, uses the provided console
     * instance to render it.
     *
     * If the message is a callable, calls it with the provided console
     * instance as an argument.
     * 
     * @param string|callable $message 
     * @param Console $console 
     */
    protected function showMessage($message, Console $console)
    {
        if (is_string($message) && ! is_callable($message)) {
            $console->writeLine($message);
            return;
        }

        if (is_callable($message)) {
            call_user_func($message, $console);
            return;
        }
    }
}
