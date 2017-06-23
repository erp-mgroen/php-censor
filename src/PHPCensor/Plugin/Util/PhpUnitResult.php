<?php

namespace PHPCensor\Plugin\Util;

/**
 * Class PhpUnitResult parses the results for the PhpUnitV2 plugin
 *
 * @author Pablo Tejada <pablo@ptejada.com>
 */
class PhpUnitResult
{
    const EVENT_TEST        = 'test';
    const EVENT_TEST_START  = 'testStart';
    const EVENT_SUITE_START = 'suiteStart';

    const SEVERITY_PASS    = 'success';
    const SEVERITY_FAIL    = 'fail';
    const SEVERITY_ERROR   = 'error';
    const SEVERITY_SKIPPED = 'skipped';

    protected $options;
    protected $arguments = [];
    protected $results;
    protected $failures = 0;
    protected $errors = [];

    public function __construct($outputFile, $buildPath = '')
    {
        $this->outputFile = $outputFile;
        $this->buildPath  = $buildPath;
    }

    /**
     * Parse the results
     *
     * @return $this
     * @throws \Exception If fails to parse the output
     */
    public function parse()
    {
        $rawResults = file_get_contents($this->outputFile);

        $events = [];
        if ($rawResults && $rawResults[0] == '{') {
            $fixedJson = '[' . str_replace('}{', '},{', $rawResults) . ']';
            $events    = json_decode($fixedJson, true);
        } elseif ($rawResults) {
            $events = json_decode($rawResults, true);
        }

        // Reset the parsing variables
        $this->results  = [];
        $this->errors   = [];
        $this->failures = 0;

        if ($events) {
            foreach ($events as $event) {
                if (isset($event['event']) && $event['event'] == self::EVENT_TEST) {
                    $this->results[] = $this->parseEvent($event);
                }
            }
        }

        return $this;
    }

    /**
     * Parse a test event
     *
     * @param array $event
     *
     * @return string[]
     */
    protected function parseEvent($event)
    {
        list($pass, $severity) = $this->getStatus($event);

        $data = [
            'pass'     => $pass,
            'severity' => $severity,
            'message'  => $this->buildMessage($event),
            'trace'    => $pass ? [] : $this->buildTrace($event),
            'output'   => $event['output'],
        ];

        if (!$pass) {
            $this->failures++;
            $this->addError($data, $event);
        }

        return $data;
    }

    /**
     * Build the status of the event
     *
     * @param $event
     *
     * @return mixed[bool,string] - The pass and severity flags
     * @throws \Exception
     */
    protected function getStatus($event)
    {
        $status = $event['status'];
        switch ($status) {
            case 'fail':
                $pass     = false;
                $severity = self::SEVERITY_FAIL;
                break;
            case 'error':
                if (strpos($event['message'], 'Skipped') === 0 || strpos($event['message'], 'Incomplete') === 0) {
                    $pass     = true;
                    $severity = self::SEVERITY_SKIPPED;
                } else {
                    $pass     = false;
                    $severity = self::SEVERITY_ERROR;
                }
                break;
            case 'pass':
                $pass     = true;
                $severity = self::SEVERITY_PASS;
                break;
            case 'warning':
                $pass     = true;
                $severity = self::SEVERITY_PASS;
                break;
            default:
                throw new \Exception("Unexpected PHPUnit test status: {$status}");
                break;
        }

        return [$pass, $severity];
    }

    /**
     * Build the message string for an event
     *
     * @param array $event
     *
     * @return string
     */
    protected function buildMessage($event)
    {
        $message = $event['test'];

        if ($event['message']) {
            $message .= PHP_EOL . $event ['message'];
        }

        return $message;
    }

    /**
     * Build a string base trace of the failure
     *
     * @param array $event
     *
     * @return string[]
     */
    protected function buildTrace($event)
    {
        $formattedTrace = [];

        if (!empty($event['trace'])) {
            foreach ($event['trace'] as $step){
                $line             = str_replace($this->buildPath, '', $step['file']) . ':' . $step['line'];
                $formattedTrace[] = $line;
            }
        }

        return $formattedTrace;
    }

    /**
     * Saves additional info for a failing test
     *
     * @param array $data
     * @param array $event
     */
    protected function addError($data, $event)
    {
        $firstTrace = end($event['trace']);
        reset($event['trace']);

        $this->errors[] = [
            'message'  => $data['message'],
            'severity' => $data['severity'],
            'file'     => str_replace($this->buildPath, '', $firstTrace['file']),
            'line'     => $firstTrace['line'],
        ];
    }

    /**
     * Get the parse results
     *
     * @return string[]
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Get the total number of failing tests
     *
     * @return int
     */
    public function getFailures()
    {
        return $this->failures;
    }

    /**
     * Get the tests with failing status
     *
     * @return string[]
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
