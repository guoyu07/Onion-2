<?php
/*
 * This file is part of the Onion package.
 *
 * (c) Yo-An Lin <cornelius.howl@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace Onion\Installer;
use Phar;
use PEARX\Installer;

class PearInstaller 
    implements InstallerInterface
{
    public $basepath = 'pear';
    public $mainInstaller;

    public function __construct($main)
    {
        $this->mainInstaller = $main;
    }

    public function install( $package , $targetVersion = null ) 
    {
        $logger = \Onion\Application::getInstance()->getLogger();
        $logger->info( "Installing {$package->name}" );

        // create temp dir
        // (PHP 5 >= 5.2.1)
        // $tmpDir = sys_get_temp_dir();

        if( ! $targetVersion )
            $targetVersion = $package->latest;


        $workspace = $this->mainInstaller->getWorkspace();
        $packageDir =  $workspace . DIRECTORY_SEPARATOR . $package->name;
        $packageSourceDir = $packageDir . DIRECTORY_SEPARATOR . $package->name . '-' . $targetVersion;

        $url = $package->getReleaseDistUrl( $targetVersion );
        $info = parse_url( $url );

        // download the package.
        $logger->info( "Downloading " . $package->name . '-' . $targetVersion . "..." );

        // switch to workspace and download the package.
        $cwd = getcwd();
        chdir( $workspace );

        $dm = new \Onion\Downloader\DownloaderManager;
        $downloader = $dm->createDownloader($logger->level == 2);
        $content = $downloader->request($url);

        // store file
        file_put_contents( basename($info['path']) , $content );
        chdir( $cwd );

        $tarFile = $workspace . DIRECTORY_SEPARATOR . basename($info['path']);
        $pearLibPath = $this->mainInstaller->libpath . DIRECTORY_SEPARATOR . $this->basepath;
        if( ! file_exists($pearLibPath) )
            mkdir( $pearLibPath , 0755, true );

        $installer = new \PEARX\Installer;
        $installer->setWorkspace( $workspace );
        $filelist = $installer->install( $tarFile, $pearLibPath );
        foreach( $filelist as $installed ) {
            $logger->debug( $installed->to , 1 );
        }
    }
}

