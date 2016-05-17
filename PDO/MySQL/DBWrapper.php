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
 * @version    DB/PDO/MySQL/DBWrapper.php 2012-03-28 09:15:47
 *
 * Wrapper Base de Donn�es d�di� au connecteur PDO pour MySQL
 */

require_once 'DBWrapperInterface.php';
require_once 'DBWrapperClassStd.php';

abstract class PDO_MySQL_DBWrapper extends DBWrapperClassStd implements DBWrapperInterface {

	public static function getPagination($db, $sql, $args, $limit_min, $limit_max, $order_by = '' ) { 

		$limit_min--;
		$sql = trim($sql);
		$order_by = trim($order_by) ;
		if ($order_by != '') {
			$order_by = 'ORDER BY '.$order_by ;
		}
		$sql .= ' ' . $order_by . ' limit '. $limit_min . ' , '. $limit_max ; 

		return self::selectBlock($db, $sql, $args ) ;
	}
	
    /*
     * M�thode permettant de r�cup�rer le dernier ID cr�� dans la BD
     * Le dernier ID est soit l'ID interne DB2, soit une s�quence DB2 dont le code est transmis en param�tre
     */
    public static function getLastInsertId($db, $sequence = '') {
    	// notion de s�quence inutilis�e avec MySQL (pour l'instant)
    	// TODO : int�grer l'utilisation de s�quence MySQL dans cette m�thode
    	$sequence = trim($sequence) ;
        $sql = "SELECT last_Insert_Id() AS LASTINSERTID";
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
			// TODO : revoir liste ci-dessous pour MySQL (les valeurs ci-dessous �tant adapt�es � DB2)
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
	 * M�thode sans effet avec MySQL 
	 * @param connexion $db
	 * @param string $cmd
	 */
	public static function executeSysCommand ($db, $cmd) {
		error_log('Warning : utilisation de la m�thode '.__METHOD__.' inop�rante sur la classe '.__CLASS__) ;
		return false ;
	}
	
}
