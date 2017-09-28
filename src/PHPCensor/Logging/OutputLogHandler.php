<?php

namespace PHPCensor\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class OutputLogHandler outputs the build log to the terminal.
 */
class OutputLogHandler extends AbstractProcessingHandler
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param OutputInterface $output
     * @param bool|string $level
     * @param bool $bubble
     */
    public function __construct(
        OutputInterface $output,
        $level = LogLevel::INFO,
        $bubble = true
    ) {
        parent::__construct($level, $bubble);
        $this->output = $output;
    }

    /**
     * Write a log entry to the terminal.
     * @param array $record
     */
    protected function write(array $record)
    {
        $this->output->writeln((string)$record['formatted']);
    }
}
