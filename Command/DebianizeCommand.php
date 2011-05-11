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
use TON\Bundle\DebianizeBundle\Debianizer\Debianizer;

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
        $workingFolder = $root . '/cache/debian';


        if (!$root) {
            throw new \InvalidArgumentException(sprintf('Invalid "root" option : "%s" is not a valid path', $config['root']));
        }

        // Version number 
        $version = $input->getArgument('version');
        if (!$version) {
            $version = '1.0';
        }

        $debianizer = new Debianizer($workingFolder);

        // Create the working environment
        $debianizer->createWorkingFolder();
        $output->writeln('Created directory cache/debian/data and cache/debian/control');

        // Destination folder
        $destinationFolder = trim($this->container->getParameter('ton_debianize.install_location'), '/');
        $destinationDepth = count(explode('/', $destinationFolder));
        $destinationRoot = str_repeat('../', ($destinationDepth - 1));
        $destinationDirectory = substr($destinationFolder, 0, strripos($destinationFolder, '/'));
        $debianizer->createFolder($destinationDirectory);
        $output->writeln('Created destination dir cache/debian/data/' . $destinationDirectory);

        // -------------------
        // Create control file
        // -------------------
        $size = $debianizer->getFolderSize($root . '/..');
        $package = $this->container->getParameter('ton_debianize.package');

        // dependencies
        $dependencies = $package['dependencies'];
        $dependenciesString = '';
        foreach ($dependencies as $dependency) {
            if ($dependenciesString != '') {
                $dependenciesString .= ', ';
            }
            $dependenciesString .= $dependency;
        }
        $controlFileTemplate = $root.'/../vendor/bundles/TON/Bundle/DebianizeBundle/Resources/control';
        $debianizer->createControlFile($controlFileTemplate, $package['name'], $package['description'], $package['maintainer'], $version, $dependenciesString, $size);


        // create root link
        $debianizer->createLink($root . '/..', $destinationFolder);
        $output->writeln('Created symlink cache/debian/data/' . $destinationFolder);

        // Create extra links
        $additionalResources = $this->container->getParameter('ton_debianize.additional_resources');
        foreach ($additionalResources as $additionalResource) {
            $source = $additionalResource['source'];
            $destination = $additionalResource['destination'];
            $source = trim($source, '/');
            $destination = trim($destination, '/');

            $destinationDepth = count(explode('/', $destination));
            $destinationRoot = str_repeat('../', ($destinationDepth - 1));
            $destinationDirectory = substr($destination, 0, strripos($destination, '/'));
            $debianizer->createFolder($destinationDirectory);
            $output->writeln('Created destination dir cache/debian/data/' . $destinationDirectory);

            $debianizer->createLink($root . '/' . $source, $destination);
            $output->writeln('Created symlink cache/debian/data/' . $destination);
        }

        // Excludes
        $excludes = $this->container->getParameter('ton_debianize.excludes');

        // Create archives
        $debianizer->createDataArchive($excludes);
        $output->writeln('Created data file cache/debian/data.tar.gz');

        $debianizer->createControlArchive();
        $output->writeln('Created data file cache/debian/control.tar.gz');

        // Create debian package
        $debianizer->createDebianPackage();
        $output->writeln('Created debian file cache/debian/debian.deb');

        // Finished
        $message = 'Debianize success';
        $output->writeln($message);
        $output->setDecorated(true);


    }
}

