<?php

namespace PHPCensor\Plugin;

use PHPCensor\Builder;
use PHPCensor\Model\Build;
use PHPCensor\Model\BuildError;
use PHPCensor\Plugin;
use PHPCensor\ZeroConfigPluginInterface;

/**
 * PHP Copy / Paste Detector - Allows PHP Copy / Paste Detector testing.
 *
 * @author Dan Cryer <dan@block8.co.uk>
 */
class PhpCpd extends Plugin implements ZeroConfigPluginInterface
{
    protected $directory;
    protected $args;

    /**
     * @var string, based on the assumption the root may not hold the code to be
     * tested, extends the base path
     */
    protected $path;

    /**
     * @var array - paths to ignore
     */
    protected $ignore;

    /**
     * @return string
     */
    public static function pluginName()
    {
        return 'php_cpd';
    }

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $builder, Build $build, array $options = [])
    {
        parent::__construct($builder, $build, $options);

        $this->path   = $this->builder->buildPath;
        $this->ignore = $this->builder->ignore;

        if (!empty($options['path'])) {
            $this->path = $this->builder->buildPath . $options['path'];
        }

        if (!empty($options['ignore'])) {
            $this->ignore = $options['ignore'];
        }
    }

    /**
     * Check if this plugin can be executed.
     * 
     * @param $stage
     * @param Builder $builder
     * @param Build   $build
     * 
     * @return bool
     */
    public static function canExecute($stage, Builder $builder, Build $build)
    {
        if ($stage == Build::STAGE_TEST) {
            return true;
        }

        return false;
    }

    /**
     * Runs PHP Copy/Paste Detector in a specified directory.
     */
    public function execute()
    {
        $ignore       = '';
        $namesExclude = ' --names-exclude ';

        foreach ($this->ignore as $item) {
            $item = rtrim($item, DIRECTORY_SEPARATOR);
            if (is_file(rtrim($this->path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $item)) {
                $ignoredFile     = explode('/', $item);
                $filesToIgnore[] = array_pop($ignoredFile);
            } else {
                $ignore .= ' --exclude ' . $item;
            }
        }

        if (isset($filesToIgnore)) {
            $filesToIgnore = $namesExclude . implode(',', $filesToIgnore);
            $ignore = $ignore . $filesToIgnore;
        }

        $phpcpd = $this->builder->findBinary('phpcpd');

        $tmpFileName = tempnam('/tmp', 'phpcpd');

        $cmd     = $phpcpd . ' --log-pmd "%s" %s "%s"';
        $success = $this->builder->executeCommand($cmd, $tmpFileName, $ignore, $this->path);

        $errorCount = $this->processReport(file_get_contents($tmpFileName));

        $this->build->storeMeta('phpcpd-warnings', $errorCount);

        unlink($tmpFileName);

        return $success;
    }

    /**
     * Process the PHPCPD XML report.
     * 
     * @param $xmlString
     * 
     * @return integer
     * 
     * @throws \Exception
     */
    protected function processReport($xmlString)
    {
        $xml = simplexml_load_string($xmlString);

        if ($xml === false) {
            $this->builder->log($xmlString);
            throw new \Exception('Could not process the report generated by PHPCpd.');
        }

        $warnings = 0;
        foreach ($xml->duplication as $duplication) {
            foreach ($duplication->file as $file) {
                $fileName = (string)$file['path'];
                $fileName = str_replace($this->builder->buildPath, '', $fileName);

                $message = <<<CPD
Copy and paste detected:

```
{$duplication->codefragment}
```
CPD;

                $this->build->reportError(
                    $this->builder,
                    'php_cpd',
                    $message,
                    BuildError::SEVERITY_NORMAL,
                    $fileName,
                    (int)$file['line'],
                    (int)$file['line'] + (int)$duplication['lines']
                );
            }

            $warnings++;
        }

        return $warnings;
    }
}
