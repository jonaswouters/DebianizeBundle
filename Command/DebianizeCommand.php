<?php
/*
 * This file is part of the DebianizeBundle project.
 *
 * (c) 21net.com <info@21net.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TON\Bundle\DebianizeBundle\Command;

use Symfony\Component\EventDispatcher\EventInterface;
use BeSimple\DeploymentBundle\Deployer\Deployer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand as BaseCommand;
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
                new InputArgument('version', InputArgument::OPTIONAL, 'The target version number', null),
                new InputArgument('development', InputArgument::OPTIONAL, 'Is this a development snapshot', null),
                new InputArgument('architecture', InputArgument::OPTIONAL, 'Package architecture', null),
            ))
        ;

        $this->setName('debianize:build');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // config elements
        $root = realpath($this->getContainer()->getParameter('kernel.root_dir'));
        $workingFolder = $root . '/cache/debian';


        if (!$root) {
            throw new \InvalidArgumentException(sprintf('Invalid "root" option : "%s" is not a valid path', $config['root']));
        }

        // Version number 
        $version = $input->getArgument('version');
        if (!$version) {
            $version = '1.0';
        }

        $development = $input->getArgument('development');
        if ($development) {
            $version .='~SNAPSHOT~' . time();
        }
        $architecture = $input->getArgument('architecture');
        if (!$architecture) {
            $architecture .= '_all';
        }

        // TODO: Create service
        $debianizer = new Debianizer($workingFolder);

        // Create the working environment
        $debianizer->createWorkingFolder();
        $output->writeln('Created directory cache/debian/data and cache/debian/control');

        // Destination folder
        $destinationFolder = trim($this->getContainer()->getParameter('ton_debianize.install_location'), '/');
        $destinationDepth = count(explode('/', $destinationFolder));
        $destinationRoot = str_repeat('../', ($destinationDepth - 1));
        $destinationDirectory = substr($destinationFolder, 0, strripos($destinationFolder, '/'));
        $debianizer->createFolder($destinationDirectory);
        $output->writeln('Created destination dir cache/debian/data/' . $destinationDirectory);

        // -------------------
        // Create control file
        // -------------------
        $size = $debianizer->getFolderSize($root . '/..');
        $package = $this->getContainer()->getParameter('ton_debianize.package');

        // dependencies
        $dependencies = $package['dependencies'];
        $dependenciesString = '';
        if ($dependencies) {
            foreach ($dependencies as $dependency) {
                if ($dependenciesString != '') {
                    $dependenciesString .= ', ';
                }
                $dependenciesString .= $dependency;
            }
        }
        $controlFileTemplate = $root.'/../vendor/bundles/TON/Bundle/DebianizeBundle/Resources/control';
        $debianizer->createControlFile($controlFileTemplate, $package['name'], $package['description'], $package['maintainer'], $version, $dependenciesString, $size);


        // create root link
        $debianizer->createLink($root . '/..', $destinationFolder);
        $output->writeln('Created symlink cache/debian/data/' . $destinationFolder);

        // Additional resources links
        $additionalResources = $this->getContainer()->getParameter('ton_debianize.additional_resources');
        if ($additionalResources) {
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
        }
        //
        // Additional control files
        $additionalControlFiles = $this->getContainer()->getParameter('ton_debianize.additional_control_files');
        if ($additionalControlFiles) {
            foreach ($additionalControlFiles as $additionalControlFile) {
                $source = $additionalControlFile['source'];
                $destination = $additionalControlFile['destination'];
                $source = trim($source, '/');

                $debianizer->createLink($root . '/' . $source, $destination, 'control');
                $output->writeln('Created symlink cache/debian/control/' . $destination);
            }
        }

        // Excludes
        $excludes = $this->getContainer()->getParameter('ton_debianize.excludes');

        // Create archives
        $debianizer->createDataArchive($excludes);
        $output->writeln('Created data file cache/debian/data.tar.gz');

        $debianizer->createControlArchive();
        $output->writeln('Created data file cache/debian/control.tar.gz');

        // Create debian package
        $fileName = $package['name'] . '_' . $version . $architecture;
        $debianizer->createDebianPackage($fileName);
        $output->writeln('Created debian file cache/debian/' . $fileName . '.deb');

        // Finished
        $message = 'Debianize success';
        $output->writeln($message);
        $output->setDecorated(true);
    }
}

