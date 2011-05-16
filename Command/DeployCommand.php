<?php

namespace TON\Bundle\DebianizeBundle\Command;

use Symfony\Component\EventDispatcher\EventInterface;
use BeSimple\DeploymentBundle\Deployer\Deployer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\Command as BaseCommand;
use Symfony\Component\Process\Process;
use TON\Bundle\DebianizeBundle\Debianizer\Ssh;

/**
 * DeployCommand 
 * 
 * @author  Jonas Wouters <jonas@21net.com>
 */
class DeployCommand extends BaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDefinition(array(
                new InputArgument('package', InputArgument::OPTIONAL, 'The package to deploy', null),
            ))
        ;

        $this->setName('debianize:deploy');
    }

    /**
     * @throws \InvalidArgumentException
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $eventDispatcher = $this->container->get('event_dispatcher');
        $logger = $this->container->get('logger');

        // config elements
        $root = realpath($this->container->getParameter('kernel.root_dir'));
        $workingFolder = $root . '/cache/debian';

        $package = $input->getArgument('package');
        if (!$package) {
            $package = $this->getLatestFile($workingFolder);
        }

        // Deploy settings
        $config = $this->container->getParameter('ton_debianize.deploy');

        $ssh = new Ssh($config);
        $ssh->connect();
        $ssh->sendFile($workingFolder.'/' . $package, $package);

        foreach ($config['commands'] as $key => $command)
        {
            $command = str_replace('{file_name}', $package, $command);

            $output->writeln('Executing command ' . $key+1);
            $output->writeln($ssh->execute($command));
        }

        if (!$root) {
            throw new \InvalidArgumentException(sprintf('Invalid "root" option : "%s" is not a valid path', $config['root']));
        }

        // Finished
        $message = 'Deployed ' . $package . ' successfully';
        $output->writeln($message);
        $output->setDecorated(true);

    }

    /**
     * Returns the newest file with the deb extension in $workingFolder
     * @param  $workingFolder
     * @return string Filename
     */
    private function getLatestFile($workingFolder)
    {
        $latestFilename = 0;
        $latest_filename = '';

        $d = dir($workingFolder);
        while (false !== ($entry = $d->read())) {
            $filepath = "{$workingFolder}/{$entry}";
            // could do also other checks than just checking whether the entry is a file
            if (is_file($filepath) && filectime($filepath) > $latestCtime) {
                $fileInfo = pathinfo($filepath);
                if ($fileInfo['extension'] == 'deb') {
                    $latestCtime = filectime($filepath);
                    $latestFilename = $entry;
                }
            }
        }
        return $latestFilename;
    }
}

