<?php

namespace TON\Bundle\DebianizeBundle\Debianizer;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use BeSimple\DeploymentBundle\Events;
use BeSimple\DeploymentBundle\Event\DeployerEvent;
use Symfony\Component\Process\Process;

class Debianizer
{

    private $workingFolder;

    public function __construct($workingFolder)
    {
        $this->workingFolder = $workingFolder;
    }

    public function getFolderSize($folder)
    {
        // Disk usage
        $command = 'du -sk --exclude=app/cache/*';
        $process = new Process($command, $folder);
        $code = $process->run();
        $size = $process->getOutput();
        $size = substr($size, 0, strpos($size, ' ' ));
        if (!$size) {
            $size = 0;
        }

        return $size;
    }

    public function createWorkingFolder()
    {
        // create debian dir
        $command = 'mkdir -p ' . $this->workingFolder;
        $process = new Process($command, $this->workingFolder);
        $code = $process->run();
        
        // create data dir
        $command = 'mkdir -p data';
        $process = new Process($command, $this->workingFolder);
        $code = $process->run();

        // create control dir
        $command = 'mkdir -p control';
        $process = new Process($command, $this->workingFolder);
        $code = $process->run();

        // debian-binary
        file_put_contents($this->workingFolder.'/debian-binary',"2.0\n");

        return true;
    }

    public function createFolder($folder)
    {
        $command = 'mkdir -p ' . $folder;
        $process = new Process($command, $this->workingFolder . '/data');
        $code = $process->run();

        return true;
    }

    public function createControlFile($controlFileTemplate, $name, $description, $maintainer, $version, $dependencies, $size)
    {
        $controlFileDestination = $this->workingFolder.'/control/control';
        $file_contents = file_get_contents($controlFileTemplate);
        $file_contents = str_replace("{{name}}",$name,$file_contents);
        $file_contents = str_replace("{{maintainer}}",$maintainer,$file_contents);
        $file_contents = str_replace("{{dependencies}}",$dependencies,$file_contents);
        $file_contents = str_replace("{{version}}",$version,$file_contents);
        $file_contents = str_replace("{{description}}",$description,$file_contents);
        $file_contents = str_replace("{{size}}",$size,$file_contents);
        file_put_contents($controlFileDestination,$file_contents);
    }

    public function createLink($source, $destination)
    {
        $command = 'ln -s '.$source.' '.$destination;
        $process = new Process($command, $this->workingFolder . '/data');
        $code = $process->run();

        return true;
    }

    public function createArchive($name, $folder, $excludes = array(), $useLeadingDotSlash = false)
    {
        $files = '*';
        if ($useLeadingDotSlash) {
            $files = './';
        }
        $excludesString = ' ';
        foreach ($excludes as $exclude) {
            $excludesString .= '--exclude="'.$exclude.'" ';
        }
        $command = 'tar -hzcf ../' . $name . $excludesString . $files;
        $process = new Process($command, $this->workingFolder . '/' . $folder);
        $code = $process->run();
    }

    public function createDataArchive($excludes)
    {
        $this->createArchive('data.tar.gz', 'data', $excludes, true); 
    }

    public function createControlArchive()
    {
        $this->createArchive('control.tar.gz', 'control'); 
    }

    public function createDebianPackage($name = 'debian')
    {
        $command = 'ar rcv ' . $name . '.deb debian-binary control.tar.gz data.tar.gz';
        $process = new Process($command, $this->workingFolder);
        $code = $process->run();

        return true;
    }

}

