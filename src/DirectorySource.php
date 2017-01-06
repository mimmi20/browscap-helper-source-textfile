<?php

namespace BrowscapHelper\Source;

use Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DirectorySource
 *
 * @author  Thomas Mueller <mimmi20@live.de>
 */
class DirectorySource implements SourceInterface
{
    /**
     * @var string
     */
    private $dir = null;

    /**
     * @param string $dir
     */
    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    /**
     * @param \Monolog\Logger                                   $logger
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param int                                               $limit
     *
     * @return \Generator
     */
    public function getUserAgents(Logger $logger, OutputInterface $output, $limit = 0)
    {
        $allLines = [];
        $files    = scandir($this->dir, SCANDIR_SORT_ASCENDING);

        foreach ($files as $filename) {
            $file = new \SplFileInfo($this->dir . DIRECTORY_SEPARATOR . $filename);

            if (!$file->isFile()) {
                continue;
            }

            $lines = file($file->getPathname());

            if (empty($lines)) {
                $output->writeln('Skipping empty file "' . $file->getPathname() . '"');
                continue;
            }

            foreach ($lines as $line) {
                if (isset($allLines[$line])) {
                    continue;
                }

                if ($limit && count($allLines) >= $limit) {
                    return;
                }

                $allLines[$line] = 1;

                yield $line;
            }
        }
    }

    /**
     * @param \Monolog\Logger                                   $logger
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return \Generator
     */
    public function getTests(Logger $logger, OutputInterface $output)
    {
        $allTests = [];
        $files    = scandir($this->dir, SCANDIR_SORT_ASCENDING);

        foreach ($files as $filename) {
            $file = new \SplFileInfo($this->dir . DIRECTORY_SEPARATOR . $filename);

            if (!$file->isFile()) {
                continue;
            }

            $lines = file($file->getPathname());

            if (empty($lines)) {
                $output->writeln('Skipping empty file "' . $file->getPathname() . '"');
                continue;
            }

            foreach ($lines as $line) {
                if (isset($allTests[$line])) {
                    continue;
                }

                $allTests[$line] = 1;

                yield [$line => []];
            }
        }
    }
}
