<?php
/**
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to gregory_jarrige@yahoo.fr so we can send you a copy immediately.
 *
 * @category   MacaronDB
 * @package    DB
 * @copyright  Copyright (c) 2012-2015 Gregory Jarrige 
 * @author     Gregory Jarrige <gregory_jarrige@yahoo.fr>
 * @license    New BSD License
 * @version    DB/DB2/IBMi/DBConnex.php 2012-03-28 09:15:47
 */


class DB2_IBMi_DBConnex {
	
	protected static $_instance;
		
	private function __construct() {
	}
	
	public static function getInstance($system, $user, $password, $options=array(), $persistent=false) {	

		if (is_array($options) && count($options)>0 ) {
			$options = self::generate_config($options) ;
		}
		
		if ($persistent === true) {	
			if (is_array($options) && count($options)>0 ) {
				self::$_instance = db2_pconnect ( $system, $user, $password, $options ) ;
			} else {
				self::$_instance = db2_pconnect ( $system, $user, $password ) ;
			}
		} else {
			if (is_array($options) && count($options)>0 ) {
				self::$_instance = db2_connect ( $system, $user, $password, $options ) ;
			} else {
				self::$_instance = db2_connect ( $system, $user, $password ) ;
			}
		}
		if (!is_resource(self::$_instance))  {
		    Throw new Exception('Erreur sur Connexion DB2 :'.db2_conn_errormsg());
		}				

		return self::$_instance;
	}

	public static function generate_config ($options=array()) {
		
		if (!is_array($options)) {
			$options = array() ;
		}
		/* 
		 * si $options ['i5_libl'] est un tableau, alors on le transforme en chaîne (postes séparés par un blanc pour db2_connect)
		 * tant qu'on ne sait pas si $options ['i5_libl'] est un tableau contenant plus d'une bibliothèque, on considère que
		 * c'est le mode DB2_I5_NAMING_OFF qui doit être retenu comme option par défaut (syntaxe full SQL)
		 */
		if (isset($options ['i5_naming']) && ($options ['i5_naming'] == DB2_I5_NAMING_ON || $options ['i5_naming'] == DB2_I5_NAMING_OFF)) {
			// ok, on ne touche à rien
		} else {
			if (!isset($options ['i5_naming']) || $options ['i5_naming'] !== true) {
				$options ['i5_naming'] = DB2_I5_NAMING_OFF;
			} else {
				$options ['i5_naming'] = DB2_I5_NAMING_ON;
			}
		}
		if (isset($options ['i5_libl']) ) {
			// tableau à transformer en chaîne de caractères si nécessaire + forçage de la syntaxe IBM
			$options ['i5_naming'] = DB2_I5_NAMING_ON;
			if (is_array($options ['i5_libl']) ) {
				$options ['i5_libl'] = implode(' ', $options ['i5_libl']);
			}			
		}

		/*
		
		Valeurs définies pour le niveau d'isolation, pour le connecteur DB2_Connect :
		DB2_I5_TXN_NO_COMMIT = 1
		DB2_I5_TXN_READ_UNCOMMITTED = 2
		DB2_I5_TXN_READ_COMMITTED = 3
		DB2_I5_TXN_REPEATABLE_READ = 4
		DB2_I5_TXN_SERIALIZABLE = 5
		
		Les valeurs peuvent être transmises au connecteur sous forme de chaîne de caractères :
		- No Commit = *NC ou *NONE ou NO_COMMIT 
		- Uncommitted Read = *UR ou *CHG ou READ_UNCOMMITTED
		- Repeatable Read = *RR ou *ALL ou REPEATABLE_READ
		- Cursor Stability = *CS ou READ_COMMITTED
		- Read Stability = *RS ou SERIALIZABLE
		
		Valeurs définies pour le niveau d'isolation, pour PDO (mot clé "CMT" ou "CommitMode" dans le DSN) :
		0 = Commit immediate (*NONE)
		1 = Read committed (*CS)
		2 = Read uncommitted (*CHG)
		3 = Repeatable read (*ALL)
		4 = Serializable (*RR)
		 */		
		if (isset($options ['i5_commit']) ) {
			// si valeur déjà de type numérique entier, alors on la considère comme valide et on la prend telle quelle
			if (!is_int($options ['i5_commit'])) {
				$options ['i5_commit'] = strtoupper($options ['i5_commit']);
				switch ($options ['i5_commit']) {
					// *NC (No Commit)
					case '*NONE' :
					case '*NC' :
					case 'NO_COMMIT': {
						$options ['i5_commit'] = DB2_I5_TXN_NO_COMMIT;
						break;
					}
					// *UR (Uncommitted Read)
					case '*UR' :
					case '*CHG' :
					case 'READ_UNCOMMITTED' : {
						$options ['i5_commit'] = DB2_I5_TXN_READ_UNCOMMITTED;
						break;
					}
					// *RR (Repeatable Read)
					case '*RR' :
					case '*ALL' :
					case 'REPEATABLE_READ' : {
						$options ['i5_commit'] = DB2_I5_TXN_REPEATABLE_READ;
						break;
					}
					// *CS (Read committed) 
					case '*CS' :
					case 'READ_COMMITTED' : {
						$options ['i5_commit'] = DB2_I5_TXN_READ_COMMITTED;
						break;
					}
					// *RR (Serializable)
					case '*RR' :
					case 'SERIALIZABLE' : {
						$options ['i5_commit'] = DB2_I5_TXN_SERIALIZABLE;
						break;
					}
					default:{
						// Mode NC par défaut
						$options ['i5_commit'] = DB2_I5_TXN_NO_COMMIT;
					}
				}
			}
		}
		
		/*
		 * Formats des données temporelles pour DB2_Connect :
		  	DB2_I5_FMT_ISO = 1
			DB2_I5_FMT_USA = 2
			DB2_I5_FMT_EUR = 3
			DB2_I5_FMT_JIS = 4
			DB2_I5_FMT_MDY = 5
			DB2_I5_FMT_DMY = 6
			DB2_I5_FMT_YMD = 7
			DB2_I5_FMT_JUL = 8
			DB2_I5_FMT_JOB = 10
		 */
		if (isset($options ['i5_date_fmt'])) {
			// si valeur déjà de type numérique entier, alors on la considère comme valide et on la prend telle quelle
			// sinon on convertit la chaîne dans la valeur numérique correspondante
			if (!is_int($options ['i5_date_fmt'])) {
				$options ['i5_date_fmt'] = strtoupper($options ['i5_date_fmt']);
				switch ($options ['i5_date_fmt']) {
					case '*ISO': {
						$options ['i5_date_fmt'] = DB2_I5_FMT_ISO ;
						break;
					}
					case '*EUR': {
						$options ['i5_date_fmt'] = DB2_I5_FMT_EUR ;
						break;
					}
					case '*JOB': {
						$options ['i5_date_fmt'] = DB2_I5_FMT_JOB ;
						break;
					}
					case '*DMY': {
						$options ['i5_date_fmt'] = DB2_I5_FMT_DMY ;
						break;
					}
					case '*YMD': {
						$options ['i5_date_fmt'] = DB2_I5_FMT_YMD ;
						break;
					}
					case '*MDY': {
						$options ['i5_date_fmt'] = DB2_I5_FMT_MDY ;
						break;
					}
					case '*USA': {
						$options ['i5_date_fmt'] = DB2_I5_FMT_USA ;
						break;
					}
					case '*JIS': {
						$options ['i5_date_fmt'] = DB2_I5_FMT_JIS ;
						break;
					}
					case '*JUL': {
						$options ['i5_date_fmt'] = DB2_I5_FMT_JUL ;
						break;
					}
					default:{
						// Format ISO par défaut
						$options ['i5_date_fmt'] = DB2_I5_FMT_ISO ;
					}
				}
			}
		}
		if (isset($options ['i5_time_fmt'])) {
			// si valeur déjà de type numérique entier, alors on la considère comme valide et on la prend telle quelle
			// sinon on convertit la chaîne dans la valeur numérique correspondante
			if (!is_int($options ['i5_time_fmt'])) {
				$options ['i5_time_fmt'] = strtoupper($options ['i5_time_fmt']);
				switch ($options ['i5_time_fmt']) {
					case '*ISO': {
						$options ['i5_time_fmt'] = DB2_I5_FMT_ISO ;
						break;
					}
					case '*EUR': {
						$options ['i5_time_fmt'] = DB2_I5_FMT_EUR ;
						break;
					}
					case '*JOB': {
						$options ['i5_time_fmt'] = DB2_I5_FMT_JOB ;
						break;
					}
					case '*DMY': {
						$options ['i5_time_fmt'] = DB2_I5_FMT_DMY ;
						break;
					}
					case '*YMD': {
						$options ['i5_time_fmt'] = DB2_I5_FMT_YMD ;
						break;
					}
					case '*MDY': {
						$options ['i5_time_fmt'] = DB2_I5_FMT_MDY ;
						break;
					}
					case '*USA': {
						$options ['i5_time_fmt'] = DB2_I5_FMT_USA ;
						break;
					}
					case '*JIS': {
						$options ['i5_time_fmt'] = DB2_I5_FMT_JIS ;
						break;
					}
					case '*JUL': {
						$options ['i5_time_fmt'] = DB2_I5_FMT_JUL ;
						break;
					}
					case '*HMS': {
						$options ['i5_time_fmt'] = DB2_I5_FMT_HMS ;
						break;
					}
					default:{
						// Format ISO par défaut
						$options ['i5_time_fmt'] = DB2_I5_FMT_ISO ;
					}
				}
			}
		}
		/*
		 * Format du séparateur de décimale pour DB2_Connect :
		 	DB2_I5_SEP_SLASH = 1
			DB2_I5_SEP_DASH = 2
			DB2_I5_SEP_PERIOD = 3
			DB2_I5_SEP_COMMA = 4
			DB2_I5_SEP_BLANK = 5
			DB2_I5_SEP_COLON = 6
			DB2_I5_SEP_JOB = 7
		 */
		if (isset($options ['i5_decimal_sep'])) {
			// si valeur déjà de type numérique entier, alors on la considère comme valide et on la prend telle quelle
			// sinon on convertit la chaîne dans la valeur numérique correspondante
			if (!is_int($options ['i5_decimal_sep'])) {
				$options ['i5_decimal_sep'] = strtoupper($options ['i5_decimal_sep']);
				switch ($options ['i5_decimal_sep']) {
					case '*SLASH': {
						$options ['i5_decimal_sep'] = DB2_I5_SEP_SLASH ;
						break;
					}
					case '*DASH': {
						$options ['i5_decimal_sep'] = DB2_I5_SEP_DASH ;
						break;
					}
					case '*PERIOD': {
						$options ['i5_decimal_sep'] = DB2_I5_SEP_PERIOD ;
						break;
					}
					case '*COMMA': {
						$options ['i5_decimal_sep'] = DB2_I5_SEP_COMMA ;
						break;
					}
					case '*BLANK': {
						$options ['i5_decimal_sep'] = DB2_I5_SEP_BLANK ;
						break;
					}
					case '*COLON': {
						$options ['i5_decimal_sep'] = DB2_I5_SEP_COLON ;
						break;
					}
					case '*JOB': {
						$options ['i5_decimal_sep'] = DB2_I5_SEP_JOB ;
						break;
					}				
					default:{
						// Séparateur par défaut
						$options ['i5_decimal_sep'] = DB2_I5_SEP_PERIOD;
					}
				}
			}		
		}
		if (! isset ( $options ['DB2_ATTR_CASE'] ) && ! isset ( $options ['db2_attr_case'] )) {
			$options ['DB2_ATTR_CASE'] = DB2_CASE_UPPER; // result set avec noms de colonnes en majuscules par défaut
		} else {
			// si les 2 postes ont été créés, c'est une erreur, un peu de ménage s'impose
			if (isset ( $options ['DB2_ATTR_CASE'] ) && isset ( $options ['db2_attr_case'] )) {
				unset ( $options ['db2_attr_case'] );
			} else {
				// si le poste a été créé en minuscule, on le recrée en majuscule
				if (! isset ( $options ['DB2_ATTR_CASE'] ) && isset ( $options ['db2_attr_case'] )) {
					$options ['DB2_ATTR_CASE'] = $options ['db2_attr_case'];
					unset ( $options ['db2_attr_case'] );
				}
			}
			// les 3 valeurs possibles sont UPPER, LOWER et NATURAL, donc peu importe la manière dont ces valeurs ont été saisies
			// (exemples : DB2_CASE_UPPER, CASE_UPPER, *UPPER, ou UPPER, en majuscules ou minuscules), on normalise les valeurs à 
			// UPPER, LOWER et NATURAL, pour faciliter leur transmission à db2_connect.
			$search_attr_case = stripos ( 'UPPER', $options ['DB2_ATTR_CASE'] );
			if ($search_attr_case !== false) {
				$options ['DB2_ATTR_CASE'] = DB2_CASE_UPPER;
			} else {
				$search_attr_case = stripos ( 'LOWER', $options ['DB2_ATTR_CASE'] );
				if ($search_attr_case !== false) {
					$options ['DB2_ATTR_CASE'] = DB2_CASE_LOWER;
				} else {
					$search_attr_case = stripos ( 'NATURAL', $options ['DB2_ATTR_CASE'] );
					if ($search_attr_case !== false) {
						$options ['DB2_ATTR_CASE'] = DB2_CASE_NATURAL;
					} else {
						$options ['DB2_ATTR_CASE'] = DB2_CASE_UPPER;
					}
				}
			}
		}
                /*
                 * En janvier 2015, le paramètre i5_override_ccsid ne fonctionne
                 * pas au moment de la connexion. Le seul moyen de disposer d'une 
                 * connexion en UTF-8 est de l'indiquer dans le fichier ibm_db2.ini
                 * en ajoutant la ligne suivante :
                 *   ibm_db2.i5_override_ccsid=1208
                 * La portion de code ci-dessous n'est donc pas opérationnelle
                 * mais elle a été conservée, au cas où Zend ferait évoluer
                 * le composant.
                 *
                if (isset($options ['CCSID'])) {
                    $option_ccsid = strtoupper($options ['CCSID']);
                    unset($options ['CCSID']) ;
                    if ($option_ccsid == 'UTF-8' || $option_ccsid == 'UTF8') {
                        $option_ccsid = '1208';
                    }
                    $options ['i5_override_ccsid'] = $option_ccsid ;
                }
                 * 
                 */		
		return $options;	
	}
		
}
