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

        $config = $this->container->getParameter('ton_debianize.root');
        $root = realpath($config['root']);

        if (!$root) {
            throw new \InvalidArgumentException(sprintf('Invalid "root" option : "%s" is not a valid path', $config['root']));
        }

        $command = $this->container->getParameter('ton_debianize.command');
        $options = $this->container->getParameter('ton_debianize.options');
        $process = new Process($command, $root);

        $this->stderr = array();
        $this->stdout = array();

        $code = $process->run(array($this, 'onStdLine'));

        $message = 'Debianize success';
        $output->writeln($message);
        $output->setDecorated(true);


    }

    public function onstdline($type, $line)
    {
        if ('out' == $type) {
            $this->stdout[] = $line;
        } else {
            $this->stderr = $line;
        }

        //$this->eventDispatcher->notify(new Event($this, 'besimple_deployer.rsync', array('line' => $line, 'type' => $type)));
    }
}

