<?php
/**
 * This file is part of the browscap-helper-source-textfile package.
 *
 * Copyright (c) 2016-2017, Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);
namespace BrowscapHelper\Source;

use FileLoader\Loader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UaResult\Browser\Browser;
use UaResult\Device\Device;
use UaResult\Engine\Engine;
use UaResult\Os\Os;
use UaResult\Result\Result;
use Wurfl\Request\GenericRequestFactory;

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
     * @var \FileLoader\Loader
     */
    private $loader = null;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output = null;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger = null;

    /**
     * @param \Psr\Log\LoggerInterface                          $logger
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param string                                            $dir
     */
    public function __construct(LoggerInterface $logger, OutputInterface $output, $dir)
    {
        $this->logger = $logger;
        $this->output = $output;
        $this->dir    = $dir;
        $this->loader = new Loader();
    }

    /**
     * @param int $limit
     *
     * @return string[]
     */
    public function getUserAgents($limit = 0)
    {
        $counter   = 0;
        $allLines  = [];
        $files     = scandir($this->dir, SCANDIR_SORT_ASCENDING);

        foreach ($files as $filename) {
            if ($limit && $counter >= $limit) {
                return;
            }

            $file = new \SplFileInfo($this->dir . DIRECTORY_SEPARATOR . $filename);

            if (!$file->isFile()) {
                continue;
            }

            $this->output->writeln('    reading file ' . str_pad($file->getPathname(), 100, ' ', STR_PAD_RIGHT));

            $this->loader->setLocalFile($file->getPathname());

            /** @var \GuzzleHttp\Psr7\Response $response */
            $response = $this->loader->load();

            /** @var \FileLoader\Psr7\Stream $stream */
            $stream = $response->getBody();

            $stream->read(1);
            $stream->rewind();

            while (!$stream->eof()) {
                $line = $stream->read(8192);

                if ($limit && $counter >= $limit) {
                    return;
                }

                if (empty($line)) {
                    continue;
                }

                if (isset($allLines[$line])) {
                    continue;
                }

                yield $line;
                $allLines[$line] = 1;
                ++$counter;
            }
        }
    }

    /**
     * @return \UaResult\Result\Result[]
     */
    public function getTests()
    {
        $allTests = [];
        $files    = scandir($this->dir, SCANDIR_SORT_ASCENDING);

        foreach ($files as $filename) {
            $file = new \SplFileInfo($this->dir . DIRECTORY_SEPARATOR . $filename);

            if (!$file->isFile()) {
                continue;
            }

            $this->output->writeln('    reading file ' . str_pad($file->getPathname(), 100, ' ', STR_PAD_RIGHT));

            $this->loader->setLocalFile($file->getPathname());

            /** @var \GuzzleHttp\Psr7\Response $response */
            $response = $this->loader->load();

            /** @var \FileLoader\Psr7\Stream $stream */
            $stream = $response->getBody();

            $stream->read(1);
            $stream->rewind();

            while (!$stream->eof()) {
                $line = $stream->read(8192);

                if (empty($line)) {
                    continue;
                }

                if (isset($allTests[$line])) {
                    continue;
                }

                $request  = (new GenericRequestFactory())->createRequestForUserAgent($line);
                $browser  = new Browser(null);
                $device   = new Device(null, null);
                $platform = new Os(null, null);
                $engine   = new Engine(null);

                yield $line => new Result($request, $device, $platform, $browser, $engine);
                $allTests[$line] = 1;
            }
        }
    }
}
