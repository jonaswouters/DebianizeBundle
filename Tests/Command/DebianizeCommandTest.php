<?php
/*
 * This file is part of the DebianizeBundle project.
 *
 * (c) 21net.com <info@21net.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TON\Bundle\DebianizeBundle\Tests\Command;

use TON\Bundle\DebianizeBundle\Command;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;

class DebianizeCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testAnnotationsBundle()
    {
        $container = $this->getMock('Symfony\\Component\\DependencyInjection\\ContainerBuilder');
        $kernel = $this->getMock('Symfony\Component\HttpKernel\Kernel', array(), array(), '', false);
        $kernel->expects($this->once())
               ->method('getContainer')
               ->will($this->returnValue($container));
        $application = new Application($kernel);


        
        $input = new StringInput("debianize:build 1.0");
        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $output->expects($this->at(0))
               ->method('writeln')
               ->with($this->equalTo("Created directory cache/debian/data and cache/debian/control"));

        $cmd = new \TON\Bundle\DebianizeBundle\Command\DebianizeCommand();
        $cmd->setApplication($application);
        $cmd->run($input, $output);
    }
}
