<?php

require __DIR__ . '/vendor/autoload.php';

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks {

	/**
	 * @return string
	 */
	private function versionGet() {

		$content = file_get_contents( __DIR__ . '/fragment-cache.php' );

		if ( false !== preg_match( '|^Version: (?P<version>.+)$|m', $content, $matches ) ) {
			return trim( $matches['version'] );
		}

		return '';
	}

	/**
	 * Sets the plugin version in header.
	 *
	 * @param string $version Version string.
	 */
	public function versionSet( $version ) {

		$this->taskReplaceInFile( 'fragment-cache.php' )
		     ->regex( '|^Version:.*$|m' )
		     ->to( 'Version: ' . $version )
		     ->run();
	}

	/**
	 * Creates release zip
	 *
	 * @param string $version
	 */
	public function makeRelease( $version = '' ) {

		if ( empty( $version ) ) {
			$version = $this->versionGet();
		}

		$name = basename( __DIR__ );

		$this->taskFileSystemStack()
		     ->mkdir( 'release' )
		     ->run();

		$this->taskCleanDir( 'release' )->run();

		$this->taskExec( 'composer' )
		     ->dir( __DIR__ . '/release' )
		     ->arg( "create-project rarst/{$name} {$name} " . $version )
		     ->arg( '--prefer-dist --no-dev' )
		     ->run();

		$this->taskExec( 'composer' )
		     ->dir( __DIR__ . "/release/{$name}" )
		     ->arg( 'dump-autoload --optimize' )
		     ->run();

		$zipFile    = "release/{$name}-{$version}.zip";
		$zipArchive = new ZipArchive();

		if ( ! $zipArchive->open( $zipFile, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE ) ) {
			die( "Failed to create archive\n" );
		}

		$finder = new Symfony\Component\Finder\Finder();
		$finder->files()->in( "release/{$name}" )->ignoreDotFiles( false );

		/** @var \Symfony\Component\Finder\SplFileInfo $file */
		foreach ( $finder as $file ) {
			$relativePathname = str_replace( '\\', '/', $file->getRelativePathname() );
			$zipArchive->addFile( $file->getRealPath(), "{$name}/" . $relativePathname );
		}

		if ( ! $zipArchive->status === ZIPARCHIVE::ER_OK ) {
			echo "Failed to write files to zip\n";
		}

		$zipArchive->close();

		$this->taskDeleteDir( "release/{$name}" )->run();
	}
}
