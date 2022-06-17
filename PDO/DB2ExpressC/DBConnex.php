<?php
/**
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @category   MacaronDB
 * @package    DB
 * @license    New BSD License
 * @version    DB/PDO/DB2ExpressC/DBConnex.php 2012-03-28 09:15:47
 */

class PDO_DB2ExpressC_DBConnex {

	protected static $_instance;
	protected static $_dsn;
	protected static $_sql_separator = '.';

	private function __construct() {

	}

	public static function getInstance($system, $user, $password, $options = array(), $persistent = false) {

		$dsn = 'odbc:DRIVER={IBM DB2 ODBC DRIVER};Hostname=' . $system . ';Port=50000;Protocol=TCPIP';
		// Attention à ne pas ajouter de ";" à la fin d'un DSN, car PDO n'apprécie pas du tout
		$dsn_temp = self::generate_dsn ( $options );
		if ($dsn_temp != '') {
			$dsn .= ';' . $dsn_temp;
		}

		/*
		 * Permet d'activer le mode Prepare/Execute qui par défaut est émulé par PDO (si le SGBD ne renvoie pas à PDO
		 * l'information comme quoi il gére lui méme la préparation des requêtes)
		 * Ne sachant pas si le driver "IBM i Access ODBC " renvoie cette information à PDO, la désactivation
		 * effectuée ici est une mesure préventive.
		 */
		$options_cnx = array(
			PDO::ATTR_EMULATE_PREPARES => FALSE,
		);
		if ($persistent === true) {
			$options_cnx [] = PDO::ATTR_PERSISTENT;
		}

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

		return self::$_instance;
	}

	/*
	 * Préparation du code SQL pour exécution d'une commande systéme IBMi
	 */
	protected static function prepare_cmd_sys($cmd) {
		/*
		 * méthode inutilisable sur cette version de DB2
		 */
		error_log('WARNING : méthode '. __METHOD__ . ' de la classe '. __CLASS__. ' inutilisable sur DB2 pour Windows') ;
		return null ;
	}

	/*
	 * paramètres de connexion DB2 i5 transformés en DSN
	 * Source documentaire pour PDO :
	 *  http://publib.boulder.ibm.com/infocenter/iseries/v5r4/index.jsp?topic=%2Frzaik%2Fconnectkeywords.htm
	 * Source documentaire pour DB2_Connect :
	 *  http://fr2.php.net/manual/fr/function.db2-connect.php
	 */
	public static function generate_config($options = array()) {

		/*
		 * méthode inopérante sur cette version de DB2
		*/
		error_log('WARNING : méthode '. __METHOD__ . ' de la classe '. __CLASS__. ' inopérante sur DB2 pour Windows') ;

		return $options;
	}

	protected static function generate_dsn($options) {
		$array_dsn = array ();

		if (isset ( $options ['database'] ) && $options ['database'] != '') {
			$array_dsn [] = 'Database=' . $options ['database'];
		}

		$dsn = implode ( ';', $array_dsn );

		return $dsn;
	}

	/*
	 * renvoie le séparateur SQL à utiliser en fonction du type de nommage déclaré
	 * ( nommage SQL => "."  ; ou nommage Systéme IBM i => "/" )
	 */
	public static function getSqlSeparator () {
		return '.' ;
	}

}
