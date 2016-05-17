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
 * @version    DBInstanceInterface.php 2012-03-28 09:15:47
 */

interface DBInstanceInterface {
	/**
	 * Constructeur 
	 * @param string $system
	 * @param string $user
	 * @param string $password
	 * @param array $options
	 * @param boolean $persistent
	 */
	function __construct($system, $user, $password, $options = array(), $persistent = false) ;
	/**
	 * Méthode renvoyant la ressource de connexion (pour DB2_connect) ou l'instance PDO 
	 */
	function getResource () ;
	
	/**
	 * Méthode permettant de récupérer le . ou le / selon le type de nommage (SQL ou système) déclaré à la connexion
	 * Le nommage système ne s'applique que dans le cas de DB2 pour IBM i, dans tous les autres cas, c'est le .
	 * qui est renvoyé
	 */
	function getSqlSeparator () ;
	
	/**
	 * Méthode destinée à récupérer le "result set" d'une requête ne renvoyant qu'une seule ligne
	 * Le résultat sera renvoyé sous la forme d'un tableau associatif à une dimension
	 * Le paramètre $fetch_mode_num permet d'obtenir le tableau sous la forme d'un tableau
	 * numéroté, plutôt qu'associatif
	 * @param string $sql
	 * @param array $args
	 * @param boolean $fetch_mode_num
	 */
	function selectOne($sql, $args = array(), $fetch_mode_num = false) ;
	
	/**
	 * Méthode destinée à récupérer le "result set" d'une requête renvoyant une ou plusieurs lignes
	 * Le résultat sera renvoyé sous la forme d'un tableau associatif à deux dimensions
	 * @param string $sql
	 * @param array $args
	 */
	function selectBlock($sql, $args = array()) ;
	
	/**
	 * Renvoie un resultset préformaté pour une intégration facile dans un champ de formulaire de type SELECT
	 * Le tableau renvoyé sera un tableau associatif à 2 dimensions, dont l'identifiant de chaque ligne
	 * sera alimenté par l'identifiant de la première colonne du result set
	 * Sous PDO, cette méthode s'appuie sur le paramètre PDO::FETCH_KEY_PAIR, mais sous 'ibm_db2', cette
	 * fonctionnalité n'existe pas et est donc simulée.
	 * @param string $sql
	 * @param array $args
	 */
	function selectKeyValuePairs($sql, $args = array()) ;
	
	/**
	 * Exécution d'une instruction autre que Select (Insert, Update, Delete, commande système...)
	 * @param string $sql
	 * @param array $args
	 * @param unknown_type $count_nb_rows
	 */
	function executeCommand($sql, $args = array(), $count_nb_rows = true) ;
	
	/**
	 * Exécution d'une commande système (opérationnel uniquement avec DB2 pour IBM i)
	 * @param string $cmd
	 */
	function executeSysCommand ($cmd) ;
	
	/**
	 * Méthode dédiée à l'appel de procédures stockées DB2
	 * Les procédures stockées DB2 peuvent être de type externe (encapsulant un programme RPG, Cobol, ou autre)
	 * ou pas. Dans le second cas, on parlera de procédure "full SQL", ou de procédure écrite en PL/SQL (qui 
	 * est le langage utilisé dans ce cas).
	 * Les 2 types de procédures stockées sont pris en charge par cette procédure, qui ne fait aucune différence. 
	 * @param string $proc_name
	 * @param string $proc_schema
	 * @param array $args
	 * @param boolean $return_resultset
	 */
	function callProcedure($proc_name, $proc_schema, &$args = array(), $return_resultset = false);
	
	/**
	 * Permet de faire référence à un "statement", en vue d'effectuer des "fetch" manuels.
	 * S'utilise conjointement avec la méthode getFetchAssoc()
	 * Pour un exemple d'utilisation, voir le code source de la méthode export2CSV().
	 * @param string $sql
	 * @param array $args
	 */
	function getStatement($sql, $args = array()) ;
	
	/**
	 * Méthode permettant de balayer les différentes lignes renvoyées par un statement 
	 * S'utilise conjointement avec la méthode getStatement() 
	 * Pour un exemple d'utilisation, voir le code source de la méthode export2CSV().
	 * @param statement $st
	 */
	function getFetchAssoc($st) ;
	
	/**
	 * Méthode à redéfinir dans chaque classe fille, la technique de pagination étant différente pour chaque base de données.
	 * Si cette méthode n'est pas redéfinie dans la classe fille, et dans le cas de PDO uniquement, 
	 * la méthode getPagination() fait appel à la méthode getScrollCursor()
	 * @param string $sql
	 * @param array $args
	 * @param integer $offset
	 * @param integer $nbl_by_page
	 * @param string $order_by
	 */
	function getPagination($sql, $args, $offset, $nbl_by_page, $order_by = '') ;
	
	/**
	 * fonction permettant de récupérer un result set via la technique du scroll cursor
	 * peut être utilisée en remplacement de la méthode getPagination()
	 * @param string $sql
	 * @param array $args
	 * @param integer $offset
	 * @param integer $nbl_by_page
	 * @param string $order_by
	 */
	function getScrollCursor($sql, $args, $offset, $nbl_by_page, $order_by = '') ;
	
	/**
	 * Méthode renvoyant le contenu d'un resultset au format CSV
	 * @param string $sql
	 * @param array $args
	 */
	function export2CSV($sql, $args = array()) ;
	
	/**
	 * Méthode renvoyant le contenu d'un resultset au format XML
	 * @param string $sql
	 * @param array $args
	 */
	function export2XML($sql, $args = array(), $tag_line = '', $gen_header=true) ;
	
	/**
	 * Méthode renvoyant le contenu d'un resultset sous la forme d'un script SQL contenant un INSERT de X lignes
	 * @param string $sql
	 * @param array $args
	 */
	function export2insertSQL($sql, $args = array()) ;
	
	/**
	 * Méthode permettant de récupérer le dernier ID créé dans la BD
	 * Méthode à réécrire dans chaque classe fille, la technique
	 * d'incrémentation étant spécifique à chaque base de données
	 * @param string $sequence
	 */
	function getLastInsertId($sequence = '') ;
	
	/**
	 * Méthode permettant de vérifier si une valeur existe bien dans une colonne
	 * peut également être utilisé pour vérifier la non existence d'une valeur
	 * avant son insertion dans une table (cas des colonnes en "clé unique"
	 * par exemple
	 * @param string $table
	 * @param string $nomcol
	 * @param unknown_type $valcol
	 * @param string $where_optionnel
	 */
	function valueIsExisting($table, $nomcol, $valcol, $where_optionnel = '') ;
	
	/**
	 * Méthode permettant de vérifier si une valeur existe bien dans une colonne
	 * mais sur une autre ligne que la ligne en cours de traitement
	 * on peut l'utiliser par exemple en modification d'enregistrement, pour
	 * empêcher qu'un code existant sur une autre ligne ne puisse être utilisé
	 * sur la ligne en cours de modification.
	 * @param string $table
	 * @param string $nomcol
	 * @param unknown_type $valcol
	 * @param unknown_type $idencours
	 * @param string $where_optionnel
	 */
	function valueIsExistingOnOtherRecord($table, $nomcol, $valcol, $idencours, $where_optionnel = '') ;
	
	/**
	 * Retourne un tableau contenant la liste des attributs PDO supportés par le driver DB2
	 * Méthode à adapter à chaque base de données (à redéfinir dans la classe fille)
	 *     	
	 */
	function getInfoDatabase() ;
	
	/**
	 * Comptage du nombre de lignes d'une table  	
	 * @param string $table       	
	 * @param string $schema        	
	 */
	function countNbRowsFromTable($table, $schema = '') ;
	
	/**
	 * Comptage du nombre de lignes renvoyées par une requête SQL   	
	 * @param string $table       	
	 * @param string $schema        	
	 */
	function countNbRowsFromSQL($sql, $args = array()) ;
	
	/**
	 * Activation du "profiler" pour analyse des performances 
	 * (alimente la log PHP avec des informations relatives aux requêtes exécutées dans ce mode)
	 */
	function setProfilerOn() ;

	/**
	 * Désactivation du "profiler" 
	 */
	function setProfilerOff() ;
	
}
