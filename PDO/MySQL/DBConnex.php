<?php
/**
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @category   MacaronDB
 * @package    DB
 * @license    New BSD License
 * @version    DB/PDO/MySQL/DBConnex.php 2012-03-28 09:15:47
 */

class PDO_MySQL_DBConnex {

	protected static $_instance;

	private function __construct() {

	}

	public static function getInstance($system, $user, $password, $options = array(), $persistent = false) {
		$dsn_array = array ();

		// Préparation du DSN
		$dsn_array [] = "mysql:host=$system";
		if (is_array ( $options )) {
			if (array_key_exists ( 'port', $options )) {
				$dsn_array [] = "port={$options['port']}";
			}
			if (array_key_exists ( 'database', $options )) {
				$dsn_array [] = "dbname={$options['database']}";
			} else {
				if (array_key_exists ( 'dbname', $options )) {
					$dsn_array [] = "dbname={$options['dbname']}";
				}
			}
		}
		$options_cnx = array(
				// Permer d'éliminer les problémes de bufférisation de requête qui apparaissent sur certains environnements LAMP.
				PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE,
				// Permet d'activer le mode Prepare/Execute qui par défaut est émulé par PDO (on se demande bien pourquoi d'ailleurs...)
				PDO::ATTR_EMULATE_PREPARES => FALSE,
		);
		if ($persistent === true) {
			$options_cnx [] = PDO::ATTR_PERSISTENT;
		}
		$dsn = implode ( ';', $dsn_array );
		try {
			self::$_instance = new PDO ( $dsn, $user, $password, $options_cnx );
			self::$_instance->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			self::$_instance->setAttribute ( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC );

			if (isset($options ['DB2_ATTR_CASE'])) {
				$casse = strtoupper($options ['DB2_ATTR_CASE']);
				if ($casse == 'LOWER') {
					self::$_instance->setAttribute ( PDO::ATTR_CASE, PDO::CASE_LOWER );
				} else {
					if ($casse == 'NATURAL') {
						self::$_instance->setAttribute ( PDO::ATTR_CASE, PDO::CASE_NATURAL );
					} else {
						self::$_instance->setAttribute ( PDO::ATTR_CASE, PDO::CASE_UPPER );
					}
				}
			}

			if (is_array ( $options ) && array_key_exists ( 'charset', $options )) {
				$options ['charset'] = trim ( strtolower ( $options ['charset'] ) );
				if ($options ['charset'] == 'utf8' || $options ['charset'] == 'utf-8') {
					self::$_instance->query ( 'SET NAMES utf8' );
					self::$_instance->query ( 'SET CHARACTER SET utf8' );
				}
			}
		} catch ( PDOException $e ) {
			error_log ( 'Erreur sur PDOException ' . $e->getMessage () );
			Throw new Exception ( $e->getMessage () );
		} catch ( Exception $e ) {
			error_log ( 'Erreur sur Exception ' . $e->getMessage () );
			Throw new Exception ( $e->getMessage () );
		}

		return self::$_instance;
	}

}
