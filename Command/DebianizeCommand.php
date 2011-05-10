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

/**
 * DebianizeCommand 
 * 
 * @author  Jonas Wouters <jonas@21net.com>
 */
class DebianizeCommand extends BaseCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setDefinition(array(
                new InputArgument('version', InputArgument::OPTIONAL, 'The target versio number', null),
            ))
        ;

        $this->setName('debianize');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $eventDispatcher = $this->container->get('event_dispatcher');
        $logger = $this->container->get('logger');

        // config elements
        $root = realpath($this->container->getParameter('kernel.root_dir'));
        $destinationFolder = trim($this->container->getParameter('ton_debianize.install_location'), '/');
        $destinationDepth = count(explode('/', $destinationFolder));
        $destinationRoot = str_repeat('../', ($destinationDepth - 1));

        if (!$root) {
            throw new \InvalidArgumentException(sprintf('Invalid "root" option : "%s" is not a valid path', $config['root']));
        }

        // create data dir
        $command = 'mkdir -p cache/debian/data';
        $process = new Process($command, $root);
        $code = $process->run();
        $output->writeln('Created directory cache/debian/data');

        // create control dir
        $command = 'mkdir -p cache/debian/control';
        $process = new Process($command, $root);
        $code = $process->run();
        $output->writeln('Created directory cache/debian/control');

        // create destination dir
        $destinationDirectory = substr($destinationFolder, 0, strripos($destinationFolder, '/'));
        $command = 'mkdir -p ' . $destinationDirectory;
        $process = new Process($command, $root . '/cache/debian/data');
        $code = $process->run();
        $output->writeln('Created destination dir cache/debian/data/' . $destinationDirectory);

        // create symlink
        $command = 'ln -s ../../../../'.$destinationRoot.' '.$destinationFolder;
        $process = new Process($command, $root . '/cache/debian/data');
        $code = $process->run();
        $output->writeln('Created symlink cache/debian/data/' . $destinationFolder);

        // Archive data
        $firstFolder = substr($destinationFolder, 0, strripos($destinationFolder, '/'));
        $command = 'tar -hzcf ../data.tar.gz --exclude="app/cache" ./';
        $process = new Process($command, $root . '/cache/debian/data');
        //$code = $process->run();
        $output->writeln('Created data file cache/debian/data.tar.gz');


        // Create control file
        // Disk usage
        $command = 'du -sk --exclude=app/cache/*';
        $process = new Process($command, $root . '/..');
        $code = $process->run();
        $size = $process->getOutput();
        $size = substr($size, 0, strpos($size, ' ' ));
        if (!$size) {
            $size = 0;
        }

        // dependencies
        $package = $this->container->getParameter('ton_debianize.package');
        print_r($package);
        $dependencies = $package['dependencies'];
        $dependenciesString = '';
        foreach ($dependencies as $dependency) {
            if ($dependenciesString != '') {
                $dependenciesString += ', ';
            }
            $dependenciesString += $dependency;
        }

        $version = $input->getArgument('version');
        if (!$version) {
            $version = '1.0';
        }

        
        $controlFile = $root.'/../vendor/bundles/TON/Bundle/DebianizeBundle/Resources/control';
        $controlFileDestination = $root.'/cache/debian/control/control';
        $file_contents = file_get_contents($controlFile);
        $file_contents = str_replace("{{name}}",$this->container->getParameter('ton_debianize.package.name'),$file_contents);
        $file_contents = str_replace("{{maintainer}}",$this->container->getParameter('ton_debianize.package.maintainer'),$file_contents);
        $file_contents = str_replace("{{dependencies}}",$dependenciesString,$file_contents);
        $file_contents = str_replace("{{version}}",$version,$file_contents);
        $file_contents = str_replace("{{description}}",$this->container->getParameter('ton_debianize.package.description'),$file_contents);
        $file_contents = str_replace("{{size}}",$size,$file_contents);
        file_put_contents($controlFileDestination,$file_contents);

        // Finished
        $message = 'Debianize success';
        $output->writeln($message);
        $output->setDecorated(true);


    }
}

