<?php

namespace JetBackup\IO;

use stdClass;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

/**
 * Process
 *
 * This class provides a wrapper around PHP's proc_open functionality, allowing
 * execution of shell commands within a PHP script. It offers methods to execute
 * commands, read output, and handle process streams.
 */

class Process {
	/**
	 * Paths to include in the environment variable.
	 */
	private const PATH = [
		'/usr/local/sbin',
		'/usr/local/bin',
		'/usr/sbin',
		'/usr/bin',
		'/sbin',
		'/bin'
	];

	/**
	 * The shell command to be executed.
	 *
	 * @var string
	 */
	private string $_command;

	/**
	 * Array of process pipes.
	 *
	 * @var array
	 */
	private array $_pipes;

	/**
	 * Object to store standard output and error output.
	 *
	 * @var stdClass
	 */
	private stdClass $_out;

	/**
	 * Constructs a new Process instance.
	 *
	 * @param string $command Shell command to be executed.
	 */
	public function __construct( string $command ) {
		$this->_command  = $command;
		$this->_pipes    = [];
		$this->_out      = new stdClass();
		$this->_out->out = '';
		$this->_out->err = '';
	}

	/**
	 * Reads from the process pipes.
	 *
	 * @return void
	 */
	private function _readFromPipes() {
		$read   = [ $this->_pipes[1], $this->_pipes[2] ];
		$write  = null;
		$except = null;
		$n      = @stream_select( $read, $write, $except, 0, 500 );

		if ( $n > 0 ) {
			foreach ( $read as $pipe ) {
				while ( $data = fread( $pipe, 8092 ) ) {
					if ( $pipe === $this->_pipes[1] ) {
						$this->_out->out .= $data;
					} elseif ( $pipe === $this->_pipes[2] ) {
						$this->_out->err .= $data;
					}
				}
			}
		}
	}

	/**
	 * Executes the command and reads the output and result code.
	 *
	 * @param array|null $output Reference to store command output.
	 * @param int|null $resultCode Reference to store command result code.
	 *
	 * @return mixed Returns the last line of the output, or false on failure.
	 */
	public function execute( ?array &$output = null, ?int &$resultCode = null ) {
		$process = proc_open(
			$this->_command,
			[
				0 => [ "pipe", "r" ],
				1 => [ "pipe", "w" ],
				2 => [ "pipe", "w" ]
			],
			$this->_pipes,
			getcwd(),
			[ 'PATH' => implode( ":", self::PATH ) ]
		);

		if ( $process === false ) {
			$output[]   = "Can't open process using `proc_open`";
			$resultCode = 1;

			return false;
		}

		fclose( $this->_pipes[0] ); // Close stdin

		stream_set_blocking( $this->_pipes[1], false );
		stream_set_blocking( $this->_pipes[2], false );

		while ( true ) {
			$status = proc_get_status( $process );

			if ( $status === false || $status['running'] === false ) {
				fclose( $this->_pipes[1] );
				fclose( $this->_pipes[2] );
				proc_close( $process );

				if ( $status === false ) {
					$output[]   = "Can't read status from process";
					$resultCode = 1;

					return false;
				}

				$resultCode = $status['exitcode'];
				$out        = trim( $this->_out->out );
				if ( $resultCode && trim( $this->_out->err ) ) {
					$out = trim( $this->_out->err );
				}
				$output = $out ? preg_split( "/\r?\n/", $out ) : [];
				break;
			}

			$this->_readFromPipes();
		}
		return ! empty( $output ) ? end( $output ) : '';
	}

	/**
	 * Static method to execute a shell command.
	 *
	 * @param string $command Command to execute.
	 * @param array|null $output Reference to store command output.
	 * @param int|null $resultCode Reference to store command result code.
	 *
	 * @return mixed Returns the last line of the output, or false on failure.
	 */
	public static function exec( string $command, ?array &$output = null, ?int &$resultCode = null ) {
		$process = new self( $command );
		//echo $command;
		return $process->execute( $output, $resultCode );
	}
}