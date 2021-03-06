<?php
/**
 * This file is part of the ramsey/pygments library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Kazuyuki Hayashi <hayashi@valnur.net>
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace Ramsey\Pygments;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * A PHP wrapper for Pygments, the Python syntax highlighter
 */
class Pygments
{
    /**
     * @var string
     */
    private $pygmentize;

    /**
     * Constructor
     *
     * @param string $pygmentize The path to pygmentize command
     */
    public function __construct($pygmentize = 'pygmentize')
    {
        $this->pygmentize = $pygmentize;
    }

    /**
     * Highlight the input code
     *
     * @param string $code      The code to highlight
     * @param string $lexer     The name of the lexer (php, html,...)
     * @param string $formatter The name of the formatter (html, ansi,...)
     * @param array  $options   An array of options
     *
     * @return string
     */
    public function highlight($code, $lexer = null, $formatter = null, $options = [])
    {
        $builder = $this->createProcessBuilder();

        if ($lexer) {
            $builder->add('-l')->add($lexer);
        } else {
            $builder->add('-g');
        }

        if ($formatter) {
            $builder->add('-f')->add($formatter);
        }

        if (count($options)) {
            foreach ($options as $key => $value) {
                $builder->add('-P')->add(sprintf('%s=%s', $key, $value));
            }
        }

        $process = $builder->setInput($code)->getProcess();

        return $this->getOutput($process);
    }

    /**
     * Gets style definition
     *
     * @param string $style    The name of the style (default, colorful,...)
     * @param string $selector The css selector
     *
     * @return string
     */
    public function getCss($style = 'default', $selector = null)
    {
        $builder = $this->createProcessBuilder();
        $builder->add('-f')->add('html');
        $builder->add('-S')->add($style);

        if ($selector) {
            $builder->add('-a')->add($selector);
        }

        return $this->getOutput($builder->getProcess());
    }

    /**
     * Guesses a lexer name based solely on the given filename
     *
     * @param string $fileName The file does not need to exist, or be readable.
     *
     * @return string
     */
    public function guessLexer($fileName)
    {
        $process = $this->createProcessBuilder()
            ->setArguments(['-N', $fileName])
            ->getProcess();

        return trim($this->getOutput($process));
    }

    /**
     * Gets a list of lexers
     *
     * @return array
     */
    public function getLexers()
    {
        $process = $this->createProcessBuilder()
            ->setArguments(['-L', 'lexer'])
            ->getProcess();

        $output = $this->getOutput($process);

        return $this->parseList($output);
    }

    /**
     * Gets a list of formatters
     *
     * @return array
     */
    public function getFormatters()
    {
        $process = $this->createProcessBuilder()
            ->setArguments(['-L', 'formatter'])
            ->getProcess();

        $output = $this->getOutput($process);

        return $this->parseList($output);
    }

    /**
     * Gets a list of styles
     *
     * @return array
     */
    public function getStyles()
    {
        $process = $this->createProcessBuilder()
            ->setArguments(['-L', 'style'])
            ->getProcess();

        $output = $this->getOutput($process);

        return $this->parseList($output);
    }

    /**
     * @return ProcessBuilder
     */
    protected function createProcessBuilder()
    {
        return ProcessBuilder::create()->setPrefix($this->pygmentize);
    }

    /**
     * @param Process $process
     * @throws \RuntimeException
     * @return string
     */
    protected function getOutput(Process $process)
    {
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    /**
     * @param string $input
     * @return array
     */
    protected function parseList($input)
    {
        $list = [];

        if (preg_match_all('/^\* (.*?):\r?\n *([^\r\n]*?)$/m', $input, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $names = explode(',', $match[1]);

                foreach ($names as $name) {
                    $list[trim($name)] = $match[2];
                }
            }
        }

        return $list;
    }
}
