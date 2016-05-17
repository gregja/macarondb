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
 * @version    DB/PDO/DB2IBMi/DBWrapper.php 2012-03-28 09:15:47
 * 
 * Wrapper Base de Données dédié au connecteur PDO pour DB2 for i
 */
require_once 'DBWrapperInterface.php';
require_once 'DBWrapperClassStd.php';

abstract class PDO_DB2IBMi_DBWrapper extends DBWrapperClassStd implements DBWrapperInterface {

	/*
	 * Technique de pagination spécifique à DB2 
	 */
	public static function getPagination($db, $sql, $args, $offset, $nbl_by_page, $order_by = '' ) { 

		if (!is_array($args)) {
			$args = array() ;
		}
		$offset = intval($offset) ;
		if ($offset <= 0) {
			$offset = 1 ;
		}	
		$nbl_by_page = intval($nbl_by_page);
		if ($nbl_by_page <= 0) {
			$nbl_by_page = 10 ;
		}
		$limit_max = $offset + $nbl_by_page - 1 ;
		
		$sql = trim($sql);
		
		$order_by = trim($order_by) ;
		if ($order_by != '') {
			$order_by = 'ORDER BY '.$order_by ;
		}
		// on recherche la position du 1er SELECT pour le compléter
		$pos = stripos ( $sql, 'select' );
		if ($pos !== false) {
			$temp = 'select row_number() over ('.$order_by.') as rn, ';
			$sql = substr_replace($sql, $temp, $pos, 6 ) ;		
		} else {
			// pagination impossible si requête ne contient pas un SELECT
			return false ;
		}
		$sql = <<<BLOC_SQL
select foo.* from (  
{$sql}
) as foo  
where foo.rn between ? and ?  
BLOC_SQL;
		/*
		 * Ajout des paramètres du "between" dans le tableau des arguments transmis à la requête
		 */
		$args [] = $offset ;
		$args [] = $limit_max ;

		return self::selectBlock($db, $sql, $args ) ;
	}
	
    /*
     * Méthode permettant de récupérer le dernier ID créé dans la BD
     * Le dernier ID est soit l'ID interne DB2, soit une séquence DB2 dont le code est transmis en paramètre
     */
    public static function getLastInsertId($db, $sequence = '') {
    	$sequence = trim($sequence) ;
		if ($sequence == '') {
			$sql = "SELECT IDENTITY_VAL_LOCAL() AS LAST_INSERT_ID FROM SYSIBM{SEPARATOR}SYSDUMMY1";
        } else {
        	$sql = "SELECT NEXT VALUE FOR {$sequence} AS LAST_INSERT_ID FROM SYSIBM{SEPARATOR}SYSDUMMY1" ;
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
	 * Retourne un tableau contenant la liste des attributs PDO supportés par le driver DB2
	 * @param unknown_type $db
	 */
	public static function getInfoDatabase($db) {

		$result = array();
		
		if ($db->getResource () instanceof PDO){
			$attributes = array(
				'DRIVER_NAME', 'ERRMODE', 'CASE', 'CLIENT_VERSION', 'DRIVER_NAME', 'ORACLE_NULLS', 'PERSISTENT'
			);
			$resource = $db->getResource () ;
			foreach ($attributes as $val) {
				$result[ "PDO::ATTR_$val" ] = $resource->getAttribute(constant("PDO::ATTR_$val")) ;
			}	
		}
		return $result;	
	}
	
	
}
