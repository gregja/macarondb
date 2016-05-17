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
	 * comme s�parateur entre nom de biblioth�que et nom de table.
	 * Donc pour savoir si le "/" ou le "."
	 * doit �tre utilis�, on se base sur le contenu du param�tre "i5_naming"
	 * d�fini dans les options de la classe ou m�thode de connexion.
	 * @param connexion $db
	 * @param string $sql
	 */
	static function changeSeparator($db, $sql);
	
	/**
	 * M�thode destin�e � r�cup�rer le "result set" d'une requ�te ne renvoyant qu'une seule ligne
	 * Le r�sultat sera renvoy� sous la forme d'un tableau associatif � une dimension
	 * Le param�tre $fetch_mode_num permet d'obtenir le tableau sous la forme d'un tableau
	 * num�rot�, plut�t qu'associatif
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 * @param boolean $fetch_mode_num
	 */
	static function selectOne($db, $sql, $args = array(), $fetch_mode_num = false);
	
	/**
	 * M�thode destin�e � r�cup�rer le "result set" d'une requ�te renvoyant une ou plusieurs lignes
	 * Le r�sultat sera renvoy� sous la forme d'un tableau associatif � deux dimensions
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 */
	static function selectBlock($db, $sql, $args = array());

	/**
	 * Renvoie un resultset pr�format� pour une int�gration facile dans un champ de formulaire de type SELECT
	 * Le tableau renvoy� sera un tableau associatif � 2 dimensions, dont l'identifiant de chaque ligne
	 * sera aliment� par l'identifiant de la premi�re colonne du result set
	 * Sous PDO, cette m�thode s'appuie sur le param�tre PDO::FETCH_KEY_PAIR, mais sous 'ibm_db2', cette
	 * fonctionnalit� n'existe pas et est donc simul�e.
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 */
	static function selectKeyValuePairs($db, $sql, $args = array());
	
	/**
	 * Ex�cution d'une instruction autre que Select (Insert, Update, Delete, commande syst�me...)
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 * @param unknown_type $count_nb_rows
	 */
	static function executeCommand($db, $sql, $args = array(), $count_nb_rows = true);

	/**
	 * Ex�cution d'une commande syst�me (op�rationnel uniquement avec DB2 pour IBM i)
	 * @param connexion $db
	 * @param string $cmd
	 */
	static function executeSysCommand ($db, $cmd) ;
	
	/**
	 * M�thode d�di�e � l'appel de proc�dures stock�es DB2
	 * Les proc�dures stock�es DB2 peuvent �tre de type externe (encapsulant un programme RPG, Cobol, ou autre)
	 * ou pas. Dans le second cas, on parlera de proc�dure "full SQL", ou de proc�dure �crite en PL/SQL (qui 
	 * est le langage utilis� dans ce cas).
	 * Les 2 types de proc�dures stock�es sont pris en charge par cette proc�dure, qui ne fait aucune diff�rence. 
	 * @param connexion $db
	 * @param string $proc_name
	 * @param string $proc_schema
	 * @param array $args
	 * @param boolean $return_resultset
	 */
	static function callProcedure($db, $proc_name, $proc_schema, &$args = array(), $return_resultset = false);
	
	/**
	 * Permet de faire r�f�rence � un "statement", en vue d'effectuer des "fetch" manuels.
	 * S'utilise conjointement avec la m�thode getFetchAssoc()
	 * Pour un exemple d'utilisation, voir le code source de la m�thode export2CSV().
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 */
	static function getStatement($db, $sql, $args = array());
	
	/**
	 * M�thode permettant de balayer les diff�rentes lignes renvoy�es par un statement 
	 * S'utilise conjointement avec la m�thode getStatement() 
	 * Pour un exemple d'utilisation, voir le code source de la m�thode export2CSV().
	 * @param statement $st
	 */
	static function getFetchAssoc($st);
	
	/**
	 * M�thode � red�finir dans chaque classe fille, la technique de pagination �tant diff�rente pour chaque base de donn�es.
	 * Si cette m�thode n'est pas red�finie dans la classe fille, et dans le cas de PDO uniquement, 
	 * la m�thode getPagination() fait appel � la m�thode getScrollCursor()
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 * @param integer $offset
	 * @param integer $nbl_by_page
	 * @param string $order_by
	 */
	static function getPagination($db, $sql, $args, $offset, $nbl_by_page, $order_by = '');

	/**
	 * fonction permettant de r�cup�rer un result set via la technique du scroll cursor
	 * peut �tre utilis�e en remplacement de la m�thode getPagination()
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 * @param integer $offset
	 * @param integer $nbl_by_page
	 * @param string $order_by
	 */
	static function getScrollCursor($db, $sql, $args, $offset, $nbl_by_page, $order_by = '' ) ;
	
	/**
	 * M�thode renvoyant le contenu d'un resultset au format CSV
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 */
	static function export2CSV($db, $sql, $args = array());

	/**
	 * M�thode renvoyant le contenu d'un resultset au format XML
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 */
	static function export2XML($db, $sql, $args = array(), $tag_line = '', $gen_header=true) ;
	
	/**
	 * M�thode renvoyant le contenu d'un resultset sous la forme d'un script SQL contenant un INSERT de X lignes
	 * @param connexion $db
	 * @param string $sql
	 * @param array $args
	 */
	static function export2insertSQL($db, $sql, $args = array());
	
	/**
	 * M�thode permettant de r�cup�rer le dernier ID cr�� dans la BD
	 * M�thode � r��crire dans chaque classe fille, la technique
	 * d'incr�mentation �tant sp�cifique � chaque base de donn�es
	 * @param connexion $db
	 * @param string $sequence
	 */
	static function getLastInsertId($db, $sequence = '');
	
	/**
	 * M�thode permettant de v�rifier si une valeur existe bien dans une colonne
	 * peut �galement �tre utilis� pour v�rifier la non existence d'une valeur
	 * avant son insertion dans une table (cas des colonnes en "cl� unique"
	 * par exemple
	 * @param connexion $db
	 * @param string $table
	 * @param string $nomcol
	 * @param unknown_type $valcol
	 * @param string $where_optionnel
	 */
	static function valueIsExisting($db, $table, $nomcol, $valcol, $where_optionnel = '');
	
	/**
	 * M�thode permettant de v�rifier si une valeur existe bien dans une colonne
	 * mais sur une autre ligne que la ligne en cours de traitement
	 * on peut l'utiliser par exemple en modification d'enregistrement, pour
	 * emp�cher qu'un code existant sur une autre ligne ne puisse �tre utilis�
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
	 * Retourne un tableau contenant la liste des attributs PDO support�s par le driver DB2
	 * M�thode � adapter � chaque base de donn�es (� red�finir dans la classe fille)
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
	 * Comptage du nombre de lignes renvoy�es par une requ�te SQL
	 * @param connexion $db       	
	 * @param string $table       	
	 * @param string $schema        	
	 */
	static function countNbRowsFromSQL($db, $sql, $args = array());
	
	/**
	 * fonction permettant de contr�ler la pr�sentation des erreurs g�n�r�es par cette classe
	 * @param connexion $db
	 * @param exception $dbexc
	 * @param string $sql
	 * @param array $args
	 */
	static function MyDBException($db, $DBexc, $sql = '', $args = array());
	      
}
