<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) 21net.com <info@21net.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TON\Bundle\DebianizeBundle\Debianizer;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use BeSimple\DeploymentBundle\Events;
use BeSimple\DeploymentBundle\Event\DeployerEvent;
use Symfony\Component\Process\Process;

/**
 * Debianizer class helps you build a debian package file without the deb helper functions.  
 * Tar, ar, mkdir and ln are the tools used.
 * 
 * @author Jonas Wouters <jonas@21net.com>
 */
class Debianizer
{

    private $workingFolder;

    /**
     * __construct 
     * 
     * @param string $workingFolder 
     */
    public function __construct($workingFolder)
    {
        $this->workingFolder = $workingFolder;
    }

    /**
     * getFolderSize 
     * 
     * @param string $folder 
     */
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

    /**
     * createWorkingFolder creates a folder structure in the specified workingFolder for creating a debian package
     * 
     * @return boolean
     */
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

    /**
     * createFolder creates a folder in the workingFolder
     * 
     * @param string $folder 
     * @return boolean
     */
    public function createFolder($folder)
    {
        $command = 'mkdir -p ' . $folder;
        $process = new Process($command, $this->workingFolder . '/data');
        $code = $process->run();

        return true;
    }

    /**
     * createControlFile creates a debian control file from a template and replaces the parameters.
     * 
     * @param string $controlFileTemplate 
     * @param string $name 
     * @param string $description 
     * @param string $maintainer 
     * @param string $version 
     * @param string $dependencies 
     * @param int $size 
     */
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

    /**
     * Create a link 
     * 
     * @param string $source 
     * @param string $destination 
     * @return boolean
     */
    public function createLink($source, $destination, $folder = 'data')
    {
        $command = 'ln -s '.$source.' '.$destination;
        $process = new Process($command, $this->workingFolder . '/' . $folder);
        $code = $process->run();

        return true;
    }

    /**
     * Create a tar.gz archive 
     * 
     * @param string $name Name of the archive
     * @param string $folder Folder used to create archive
     * @param array $excludes Files, folders or pattern to exclude from the archive
     * @param boolean $useLeadingDotSlash add a ./ in front of the folder
     * @return boolean
     */
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
        $command = 'tar --hard-dereference -hzcf ../' . $name . $excludesString . $files;
        $process = new Process($command, $this->workingFolder . '/' . $folder);
        $code = $process->run();

        return true;
    }

    /**
     * Create a data.tar.gz archive from the data folder. 
     * 
     * @param array $excludes Files, folders or pattern to exclude from the archive
     * @return boolean
     */
    public function createDataArchive($excludes)
    {
        return $this->createArchive('data.tar.gz', 'data', $excludes, true); 
    }

    /**
     * Create a control.tar.gz archive from the control folder. 
     * 
     * @return boolean
     */
    public function createControlArchive()
    {
        return $this->createArchive('control.tar.gz', 'control'); 
    }

    /**
     * Create a deb package. 
     * 
     * @param string $name The name of the file without extension
     * @return boolean
     */
    public function createDebianPackage($name = 'debian')
    {
        $command = 'ar rcv ' . $name . '.deb debian-binary control.tar.gz data.tar.gz';
        $process = new Process($command, $this->workingFolder);
        $code = $process->run();

        return true;
    }

}

