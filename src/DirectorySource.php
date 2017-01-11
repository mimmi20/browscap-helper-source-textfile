<?php

namespace BrowscapHelper\Source;

use FileLoader\Loader;
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
     * @var \FileLoader\Loader
     */
    private $loader = null;

    /**
     * @param string $dir
     */
    public function __construct($dir)
    {
        $this->dir    = $dir;
        $this->loader = new Loader();
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

                $test = [
                    'ua'         => $line,
                    'properties' => [
                        'Browser_Name'            => null,
                        'Browser_Type'            => null,
                        'Browser_Bits'            => null,
                        'Browser_Maker'           => null,
                        'Browser_Modus'           => null,
                        'Browser_Version'         => null,
                        'Platform_Codename'       => null,
                        'Platform_Marketingname'  => null,
                        'Platform_Version'        => null,
                        'Platform_Bits'           => null,
                        'Platform_Maker'          => null,
                        'Platform_Brand_Name'     => null,
                        'Device_Name'             => null,
                        'Device_Maker'            => null,
                        'Device_Type'             => null,
                        'Device_Pointing_Method'  => null,
                        'Device_Dual_Orientation' => null,
                        'Device_Code_Name'        => null,
                        'Device_Brand_Name'       => null,
                        'RenderingEngine_Name'    => null,
                        'RenderingEngine_Version' => null,
                        'RenderingEngine_Maker'   => null,
                    ],
                ];

                yield [$line => $test];
                $allTests[$line] = 1;
            }
        }
    }
}
