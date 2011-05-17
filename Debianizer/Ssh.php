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
 * Ssh class allows you to interact via ssh or scp with the remote server
 * 
 * @author Jonas Wouters <jonas@21net.com>
 */
class Ssh
{
    /**
     * connection information
     * 
     * @var array
     */
    private $connection;

    private $session;
    private $shell;

    /**
     * __construct 
     * 
     * @param string $workingFolder 
     */
    public function __construct(array $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Connect and create an ssh session
     * 
     * @throws \InvalidArgumentException|\RuntimeException
     * @return void
     */
    public function connect()
    {
        $this->session = ssh2_connect($this->connection['host']);

        if (!$this->session) {
            throw new \InvalidArgumentException(sprintf('SSH connection failed on "%s:%s"', $this->connection['host'], $this->connection['ssh_port']));
        }

        if (isset($this->connection['username']) && isset($this->connection['pubkey_file']) && isset($this->connection['privkey_file'])) {
            if (!ssh2_auth_pubkey_file($this->connection['username'], $this->connection['pubkey_file'], $this->connection['privkey_file'], $this->connection['passphrase'])) {
                throw new \InvalidArgumentException(sprintf('SSH authentication failed for user "%s" with public key "%s"', $this->connection['username'], $this->connection['pubkey_file']));
            }
        } else if ($this->connection['username'] && $this->connection['password']) {
            if (!ssh2_auth_password($this->session, $this->connection['username'], $this->connection['password'])) {
                throw new \InvalidArgumentException(sprintf('SSH authentication failed for user "%s"', $this->connection['username']));
            }
        }

        $this->shell = ssh2_shell($this->session);

        if (!$this->shell) {
            throw new \RuntimeException(sprintf('Failed opening "%s" shell', $this->config['shell']));
        }
    }        

    /**
     * send a file via scp
     * 
     * @param string $source Path to the local file. 
     * @param string $destination Path to the remote file.
     * @return boolean Returns TRUE on success or FALSE on failure
     */
    public function sendFile($source, $destination)
    {
        return ssh2_scp_send($this->session, $source, $destination);
    }

    /**
     * Execute command via ssh
     * 
     * @param  $command
     * @return String Output
     */
    public function execute($command)
    {
        $outStream = ssh2_exec($this->session, $command);
        $errStream = ssh2_fetch_stream($outStream, SSH2_STREAM_STDERR);

        stream_set_blocking($outStream, true);
        stream_set_blocking($errStream, true);

        $strOut = stream_get_contents($outStream);

        fclose($outStream);
        fclose($errStream);

        return $strOut;
    }


}

