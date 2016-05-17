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
 * @version    DB/PDO/DB2ExpressC/DBWrapper.php 2012-03-28 09:15:47
 * 
 * Wrapper Base de Donn�es d�di� au connecteur PDO pour DB2 Express C
 */
require_once 'DBWrapperInterface.php';
require_once 'DBWrapperClassStd.php';

abstract class PDO_DB2ExpressC_DBWrapper extends DBWrapperClassStd implements DBWrapperInterface {

	/*
	 * Technique de pagination sp�cifique � DB2 
	 */
	public static function getPagination($db, $sql, $args, $offset, $nbl_by_page, $order_by = '' ) { 

		$limit_max = $offset + $nbl_by_page - 1 ;
		$sql = trim($sql);
		$order_by = trim($order_by) ;
		if ($order_by != '') {
			$order_by = 'ORDER BY '.$order_by ;
		}
		// on recherche la position du 1er SELECT pour le compl�ter
		$pos = stripos ( $sql, 'select' );
		if ($pos !== false) {
			$sql = substr_replace($sql, 'select row_number() over ('.$order_by.') as rn, ', $pos, 6 ) ;
		} else {
			// pagination impossible si requ�te ne contient pas un SELECT
			return false ;
		}
		$sql = <<<BLOC_SQL
select foo.* from (  
{$sql}
) as foo  
where foo.rn between {$offset} and {$limit_max} 
BLOC_SQL;

		return self::selectBlock($db, $sql, $args ) ;
	}
	
    /*
     * M�thode permettant de r�cup�rer le dernier ID cr�� dans la BD
     * Le dernier ID est soit l'ID interne DB2, soit une s�quence DB2 dont le code est transmis en param�tre
     */
    public static function getLastInsertId($db, $sequence = '') {
    	$sequence = trim($sequence) ;
		if ($sequence == '') {
			$sql = "SELECT IDENTITY_VAL_LOCAL() FROM SYSIBM{SEPARATOR}SYSDUMMY1";
        } else {
        	$sql = "SELECT NEXT VALUE FOR {$sequence} FROM SYSIBM{SEPARATOR}SYSDUMMY1" ;
        }
		$data = self::selectOne($db, $sql, array(), true);
		if (is_array($data) && isset($data[0])) {
			return $data[0];
		} else {
			return false ;
		}       
    }
	
	/**
	 * 
	 * Retourne un tableau contenant la liste des attributs PDO support�s par le driver DB2
	 * @param unknown_type $db
	 */
	public static function getInfoDatabase($db) {

		$result = array();
		
		if ($db instanceof PDO){
			$attributes = array(
				"ERRMODE", "CASE", "CLIENT_VERSION", "DRIVER_NAME", "ORACLE_NULLS", "PERSISTENT"
			);
	
			foreach ($attributes as $val) {
				$result[ "PDO::ATTR_$val" ] = $db->getAttribute(constant("PDO::ATTR_$val")) ;
			}	
		}
		return $result;	
	}

	/**
	 * M�thode sans effet avec DB2 Express C
	 * @param connexion $db
	 * @param string $cmd
	 */
	public static function executeSysCommand ($db, $cmd) {
		error_log('Warning : utilisation de la m�thode '.__METHOD__.' inop�rante sur la classe '.__CLASS__) ;
		return false ;
	}
	
	
}
