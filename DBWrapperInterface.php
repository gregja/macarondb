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
 * @version    DBWrapperInterface.php 2012-03-28 09:15:47
 */

interface DBWrapperInterface {
	
	/**
	 * La fonction db2_connect permet l'utilisation de la syntaxe IBM avec le "/" au lieu du "."
	 * comme séparateur entre nom de bibliothèque et nom de table.
	 * Donc pour savoir si le "/" ou le "."
	 * doit être utilisé, on se base sur le contenu du paramètre "i5_naming"
	 * défini dans les options de la classe ou méthode de connexion.
	 * @param connexion $db
	 * @param string $sql
	 */
	static function changeSeparator($db, $sql);
	
	/**
	 * Méthode destinée à récupérer le "result set" d'une requête ne renvoyant qu'une seule ligne
	 * Le résultat sera renvoyé sous la forme d'un tableau associatif à une dimension
	 * Le paramètre $fetch_mode_num permet d'obtenir le tableau sous la forme d'un tableau
	 * numéroté, plutôt qu'associatif
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 * @param boolean $fetch_mode_num
	 */
	static function selectOne($db, $sql, $args = array(), $fetch_mode_num = false);
	
	/**
	 * Méthode destinée à récupérer le "result set" d'une requête renvoyant une ou plusieurs lignes
	 * Le résultat sera renvoyé sous la forme d'un tableau associatif à deux dimensions
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 */
	static function selectBlock($db, $sql, $args = array());

	/**
	 * Renvoie un resultset préformaté pour une intégration facile dans un champ de formulaire de type SELECT
	 * Le tableau renvoyé sera un tableau associatif à 2 dimensions, dont l'identifiant de chaque ligne
	 * sera alimenté par l'identifiant de la première colonne du result set
	 * Sous PDO, cette méthode s'appuie sur le paramètre PDO::FETCH_KEY_PAIR, mais sous 'ibm_db2', cette
	 * fonctionnalité n'existe pas et est donc simulée.
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 */
	static function selectKeyValuePairs($db, $sql, $args = array());
	
	/**
	 * Exécution d'une instruction autre que Select (Insert, Update, Delete, commande système...)
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 * @param unknown_type $count_nb_rows
	 */
	static function executeCommand($db, $sql, $args = array(), $count_nb_rows = true);

	/**
	 * Exécution d'une commande système (opérationnel uniquement avec DB2 pour IBM i)
	 * @param connexion $db
	 * @param string $cmd
	 */
	static function executeSysCommand ($db, $cmd) ;
	
	/**
	 * Méthode dédiée à l'appel de procédures stockées DB2
	 * Les procédures stockées DB2 peuvent être de type externe (encapsulant un programme RPG, Cobol, ou autre)
	 * ou pas. Dans le second cas, on parlera de procédure "full SQL", ou de procédure écrite en PL/SQL (qui 
	 * est le langage utilisé dans ce cas).
	 * Les 2 types de procédures stockées sont pris en charge par cette procédure, qui ne fait aucune différence. 
	 * @param connexion $db
	 * @param string $proc_name
	 * @param string $proc_schema
	 * @param array $args
	 * @param boolean $return_resultset
	 */
	static function callProcedure($db, $proc_name, $proc_schema, &$args = array(), $return_resultset = false);
	
	/**
	 * Permet de faire référence à un "statement", en vue d'effectuer des "fetch" manuels.
	 * S'utilise conjointement avec la méthode getFetchAssoc()
	 * Pour un exemple d'utilisation, voir le code source de la méthode export2CSV().
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 */
	static function getStatement($db, $sql, $args = array());
	
	/**
	 * Méthode permettant de balayer les différentes lignes renvoyées par un statement 
	 * S'utilise conjointement avec la méthode getStatement() 
	 * Pour un exemple d'utilisation, voir le code source de la méthode export2CSV().
	 * @param statement $st
	 */
	static function getFetchAssoc($st);
	
	/**
	 * Méthode à redéfinir dans chaque classe fille, la technique de pagination étant différente pour chaque base de données.
	 * Si cette méthode n'est pas redéfinie dans la classe fille, et dans le cas de PDO uniquement, 
	 * la méthode getPagination() fait appel à la méthode getScrollCursor()
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 * @param integer $offset
	 * @param integer $nbl_by_page
	 * @param string $order_by
	 */
	static function getPagination($db, $sql, $args, $offset, $nbl_by_page, $order_by = '');

	/**
	 * fonction permettant de récupérer un result set via la technique du scroll cursor
	 * peut être utilisée en remplacement de la méthode getPagination()
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 * @param integer $offset
	 * @param integer $nbl_by_page
	 * @param string $order_by
	 */
	static function getScrollCursor($db, $sql, $args, $offset, $nbl_by_page, $order_by = '' ) ;
	
	/**
	 * Méthode renvoyant le contenu d'un resultset au format CSV
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 */
	static function export2CSV($db, $sql, $args = array());

	/**
	 * Méthode renvoyant le contenu d'un resultset au format XML
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 */
	static function export2XML($db, $sql, $args = array(), $tag_line = '', $gen_header=true) ;
	
	/**
	 * Méthode renvoyant le contenu d'un resultset sous la forme d'un script SQL contenant un INSERT de X lignes
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 */
	static function export2insertSQL($db, $sql, $args = array());
	
	/**
	 * Méthode permettant de récupérer le dernier ID créé dans la BD
	 * Méthode à réécrire dans chaque classe fille, la technique
	 * d'incrémentation étant spécifique à chaque base de données
	 * @param connexion $db
	 * @param string $sequence
	 */
	static function getLastInsertId($db, $sequence = '');
	
	/**
	 * Méthode permettant de vérifier si une valeur existe bien dans une colonne
	 * peut également être utilisé pour vérifier la non existence d'une valeur
	 * avant son insertion dans une table (cas des colonnes en "clé unique"
	 * par exemple
	 * @param connexion $db
	 * @param string $table
	 * @param string $nomcol
	 * @param unknown_type $valcol
	 * @param string $where_optionnel
	 */
	static function valueIsExisting($db, $table, $nomcol, $valcol, $where_optionnel = '');
	
	/**
	 * Méthode permettant de vérifier si une valeur existe bien dans une colonne
	 * mais sur une autre ligne que la ligne en cours de traitement
	 * on peut l'utiliser par exemple en modification d'enregistrement, pour
	 * empêcher qu'un code existant sur une autre ligne ne puisse être utilisé
	 * sur la ligne en cours de modification.
	 * @param connexion $db
	 * @param string $table
	 * @param string $nomcol
	 * @param unknown_type $valcol
	 * @param unknown_type $idencours
	 * @param string $where_optionnel
	 */
	static function valueIsExistingOnOtherRecord($db, $table, $nomcol, $valcol, $idencours, $where_optionnel = '');
	
	/**
	 * Retourne un tableau contenant la liste des attributs PDO supportés par le driver DB2
	 * Méthode à adapter à chaque base de données (à redéfinir dans la classe fille)
	 * 
	 * @param connexion $db       	
	 */
	static function getInfoDatabase($db);
	
	/**
	 * Comptage du nombre de lignes d'une table
	 * @param connexion $db       	
	 * @param string $table       	
	 * @param string $schema        	
	 */
	static function countNbRowsFromTable($db, $table, $schema = '');
	
	/**
	 * Comptage du nombre de lignes renvoyées par une requête SQL
	 * @param connexion $db       	
	 * @param string $table       	
	 * @param string $schema        	
	 */
	static function countNbRowsFromSQL($db, $sql, $args = array());
	
	/**
	 * fonction permettant de contrôler la présentation des erreurs générées par cette classe
	 * @param connexion $db
	 * @param exception $dbexc
	 * @param string $sql
	 * @param array $args
	 */
	static function MyDBException($db, $DBexc, $sql = '', $args = array());
	      
}
