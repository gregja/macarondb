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
 * @version    DB/DB2/IBMi/DBWrapper.php 2012-03-28 09:15:47
 *
 * Wrapper Base de Donn�es d�di� au connecteur odbc_connect
 */
require_once 'DBWrapperInterface.php';

abstract class ODBC_IBMi_DBWrapper implements DBWrapperInterface {
    /*
     * La fonction odbc_connect permet l'utilisation de la syntaxe IBM avec 
     * le "/" au lieu du "."
     * comme s�parateur entre nom de biblioth�que et nom de table. Donc pour 
     * savoir si le "/" ou le "."
     * doit �tre utilis�, on se base sur le contenu du param�tre "i5_naming" 
     * d�fini dans les options de odbc_connect.
     */

    public static function changeSeparator($db, $sql) {

        $pos = strpos($sql, '{SEPARATOR}');
        if ($pos !== false) {
            $sql = str_replace('{SEPARATOR}', $db->getSqlSeparator(), $sql);
        }
        return $sql;
    }

    public static function selectOne($db, $sql, $args = array(), $fetch_mode_num = false) {

        $sql = self::changeSeparator($db, $sql);
        $result = array();

        if (!is_array($args)) {
            if (trim($args) != '') {
                $args = array($args);
            } else {
                $args = array();
            }
        }

        $profiler_status = $db->getProfilerStatus();
        if ($profiler_status) {
            $profiler_start = self::getMicrotime();
            $profiler_nb_rows = 0;
        }
        try {
            $st = odbc_prepare($db->getResource(), $sql);
            if (!$st) {
                self::MyDBError($db, 'selectOne/odbc_prepare', $sql, $args);
                $result = null;
            } else {
                if (!odbc_execute($st, $args)) {
                    self::MyDBError($db, 'selectOne/odbc_execute', $sql, $args);
                    $result = null;
                } else {
                    /*
                     * par d�faut c'est le mode "fetch array" qui est utilis�, 
                     * mais dans certains cas le mode "fetch num" peut �tre utile
                     */
                    if ($fetch_mode_num === true) {
                        $tmp_result = odbc_fetch_array($st);
                        $result = array();
                        foreach($tmp_result as $tmp_result_key=>$tmp_result_val) {
                            $result[] = $tmp_result_val ;
                        }
                    } else {
                        $result = odbc_fetch_array($st);
                    }
                }
                odbc_free_result($st);
            }
            unset($st);
            if ($profiler_status) {
                $profiler_nb_rows = 1;
                self::logProfiler($profiler_start, $sql, $args, $profiler_nb_rows, 'good');
            }

            return $result;
        } catch (Exception $e) {
            if ($profiler_status) {
                self::logProfiler($profiler_start, $sql, $args, $profiler_nb_rows, 'bad');
            }

            self::MyDBException($db, $e, $sql, $args);
        }
    }

    public static function selectBlock($db, $sql, $args = array()) {

        $sql = self::changeSeparator($db, $sql);

        $rows = array();
        if (!is_array($args)) {
            if (trim($args) != '') {
                $args = array($args);
            } else {
                $args = array();
            }
        }
        $profiler_status = $db->getProfilerStatus();
        if ($profiler_status) {
            $profiler_start = self::getMicrotime();
            $profiler_nb_rows = 0;
        }
        try {
            $st = odbc_prepare($db->getResource(), $sql);
            if (!$st) {
                self::MyDBError($db, 'selectBlock/odbc_prepare', $sql, $args);
                $rows = null;
            } else {
                if (!odbc_execute($st, $args)) {
                    self::MyDBError($db, 'selectBlock/odbc_execute', $sql, $args);
                    $rows = null;
                } else {
                    $row = odbc_fetch_array($st);
                    while ($row != false) {
                        $rows [] = $row;
                        $row = odbc_fetch_array($st);
                        if ($profiler_status) {
                            $profiler_nb_rows++;
                        }
                    }
                }
                odbc_free_result($st);
            }
            unset($st);
            if ($profiler_status) {
                self::logProfiler($profiler_start, $sql, $args, $profiler_nb_rows, 'good');
            }

            return $rows;
        } catch (Exception $e) {
            if ($profiler_status) {
                self::logProfiler($profiler_start, $sql, $args, $profiler_nb_rows, 'bad');
            }

            self::MyDBException($db, $e, $sql, $args);
        }
    }

    public static function selectKeyValuePairs($db, $sql, $args = array()) {

        $sql = self::changeSeparator($db, $sql);

        $rows = array();
        if (!is_array($args)) {
            if (trim($args) != '') {
                $args = array($args);
            } else {
                $args = array();
            }
        }
        $profiler_status = $db->getProfilerStatus();
        if ($profiler_status) {
            $profiler_start = self::getMicrotime();
            $profiler_nb_rows = 'unknown on FETCH_KEY_PAIR';
        }
        try {

            $st = odbc_prepare($db->getResource(), $sql);
            if (!$st) {
                self::MyDBError($db, 'selectBlock/odbc_prepare', $sql, $args);
                $rows = null;
            } else {
                if (!odbc_execute($st, $args)) {
                    self::MyDBError($db, 'selectBlock/odbc_execute', $sql, $args);
                    $rows = null;
                } else {
                    $row = odbc_fetch_array($st);
                    while ($row != false) {
                        $rows [$row[0]] = $row[1];
                        $row = odbc_fetch_array($st);
                    }
                }
                odbc_free_result($st);
            }
            unset($st);
            if ($profiler_status) {
                self::logProfiler($profiler_start, $sql, $args, $profiler_nb_rows, 'good');
            }

            return $rows;
        } catch (Exception $e) {
            if ($profiler_status) {
                self::logProfiler($profiler_start, $sql, $args, $profiler_nb_rows, 'bad');
            }

            self::MyDBException($db, $e, $sql, $args);
        }
    }

    public static function executeCommand($db, $sql, $args = array(), $count_nb_rows = true) {

        $sql = self::changeSeparator($db, $sql);

        if (!is_array($args)) {
            if (trim($args) != '') {
                $args = array($args);
            } else {
                $args = array();
            }
        }
        $nbrows = 0;
        $profiler_status = $db->getProfilerStatus();
        if ($profiler_status) {
            $profiler_start = self::getMicrotime();
            $profiler_nb_rows = 0;
        }
        try {

            $st = odbc_prepare($db->getResource(), $sql);
            if (!$st) {
                self::MyDBError($db, 'executeCommand/odbc_prepare', $sql, $args);
                $nbrows = null;
            } else {
                if (!odbc_execute($st, $args)) {
                    self::MyDBError($db, 'executeCommand/odbc_execute', $sql, $args);
                    $nbrows = null;
                } else {
                    if ($count_nb_rows === true) {
                        $nbrows = odbc_num_rows($st);
                    }
                }
                odbc_free_result($st);
            }
            unset($st);
            if ($profiler_status) {
                $profiler_nb_rows = $nbrows;
                self::logProfiler($profiler_start, $sql, $args, $profiler_nb_rows, 'good');
            }

            return $nbrows;
        } catch (Exception $e) {
            if ($profiler_status) {
                self::logProfiler($profiler_start, $sql, $args, $profiler_nb_rows, 'bad');
            }

            self::MyDBException($db, $e, $sql, $args);
        }
    }

    public static function executeSysCommand($db, $cmd) {
        $cmd = trim($cmd);
        $cmd_length = strlen($cmd);
        $cmd2 = "CALL QCMDEXC ('{$cmd}', {$cmd_length})";
        return self::executeCommand($db, $cmd2);
    }

    public static function callProcedure($db, $proc_name, $proc_schema, 
            &$args = array(), $return_resultset = false) {

        $proc_name = trim($proc_name);
        $proc_schema = trim($proc_schema);
        if ($proc_schema == '') {
            $proc_sql = $proc_name;
        } else {
            $proc_sql = $proc_schema . '{SEPARATOR}' . $proc_name;
        }

        $sql = 'CALL ' . $proc_sql;
        $sql = self::changeSeparator($db, $sql);

        if (is_array($args) && count($args) > 0) {
            $jokers = array();
            $args_inc = 0;
            foreach ($args as $key => $arg) {
                $jokers [] = '?';
                $args_inc++;
            }
            $sql .= ' ( ' . implode(', ', $jokers) . ' ) ';
        }
        try {
            $resultset = array();

            $st = odbc_prepare($db->getResource(), $sql);
            if (!$st) {
                self::MyDBError($db, 'executeCommand/odbc_prepare', $sql, $args);
            } else {
                $args_inc = 0;
                foreach ($args as $key => $arg) {
                    /*
                     * cr�e artificiellement une variable ayant pour nom 
                     * le contenu de la variable $key
                     */
                    $$key = $args[$key]['value'];
                    $args_inc++;
                    $arg['type'] = strtolower($arg['type']);

                    switch ($arg['type']) {
                        case 'out': {
                                db2_bind_param($st, $args_inc, $key, DB2_PARAM_OUT);
                                break;
                            }
                        case 'inout': {
                                db2_bind_param($st, $args_inc, $key, DB2_PARAM_INOUT);
                                break;
                            }
                        default: {
                                db2_bind_param($st, $args_inc, $key, DB2_PARAM_IN);
                                break;
                            }
                    }
                }
                if (!odbc_execute($st)) {
                    self::MyDBError($db, 'executeCommand/odbc_execute', $sql, $args);
                } else {
                    foreach ($args as $key => $arg) {
                        $arg['type'] = strtolower($arg['type']);
                        if ($arg['type'] == 'out' || $arg['type'] == 'inout') {
                            $args[$key]['value'] = $$key;
                        }
                    }
                    if ($return_resultset === true) {
                        $row = odbc_fetch_array($st);
                        while ($row != false) {
                            $resultset [] = $row;
                            $row = odbc_fetch_array($st);
                        }
                    }
                }
                odbc_free_result($st);
            }
            unset($st);
            return $resultset;
        } catch (Exception $e) {
            self::MyDBException($db, $e, $sql, $args);
        }
    }

    public static function getStatement($db, $sql, $args = array()) {

        $sql = self::changeSeparator($db, $sql);

        if (!is_array($args)) {
            if (trim($args) != '') {
                $args = array($args);
            } else {
                $args = array();
            }
        }
        $profiler_status = $db->getProfilerStatus();
        if ($profiler_status) {
            $profiler_start = self::getMicrotime();
        }
        try {

            $st = odbc_prepare($db->getResource(), $sql);
            if (!$st) {
                self::MyDBError($db, 'getStatement/odbc_prepare', $sql, $args);
            } else {
                if (!odbc_execute($st, $args)) {
                    self::MyDBError($db, 'getStatement/odbc_execute', $sql, $args);
                    $st = false;
                }
            }
            if ($profiler_status) {
                self::logProfiler($profiler_start, $sql, $args, null, 
                        'good - timer on first fetch only');
            }

            return $st;
        } catch (Exception $e) {
            if ($profiler_status) {
                self::logProfiler($profiler_start, $sql, $args, null, 
                        'bad - timer on first fetch only');
            }

            self::MyDBException($db, $e, $sql, $args);
        }
    }

    public static function getFetchAssoc($st) {
        if (!$st) {
            return false;
        } else {
            return odbc_fetch_array($st);
        }
    }

    public static function getPagination($db, $sql, $args, $offset, 
            $nbl_by_page, $order_by = '') {

        if (!is_array($args)) {
            $args = array();
        }
        $offset = intval($offset);
        if ($offset <= 0) {
            $offset = 1;
        }
        $nbl_by_page = intval($nbl_by_page);
        if ($nbl_by_page <= 0) {
            $nbl_by_page = 10;
        }
        $limit_max = $offset + $nbl_by_page - 1;

        $sql = trim($sql);

        $order_by = trim($order_by);
        if ($order_by != '') {
            $order_by = 'ORDER BY ' . $order_by;
        }
        // on recherche la position du 1er SELECT pour le compl�ter
        $pos = stripos($sql, 'select');
        if ($pos !== false) {
            $temp = 'select row_number() over (' . $order_by . ') as rn, ';
            $sql = substr_replace($sql, $temp, $pos, 6);
        } else {
            // pagination impossible si requ�te ne contient pas un SELECT
            return false;
        }
        $sql = <<<BLOC_SQL
select foo.* from (  
{$sql}
) as foo  
where foo.rn between ? and ? 
BLOC_SQL;
        /*
         * Ajout des param�tres du "between" dans le tableau des arguments 
         * transmis � la requ�te
         */
        $args [] = $offset;
        $args [] = $limit_max;

        return self::selectBlock($db, $sql, $args);
    }

    /*
     * Pagination via la technique du Scroll Cursor telle qu'elle est 
     * impl�ment�e dans odbc_connect
     */

    public static function getScrollCursor($db, $sql, $args, $offset, 
            $nbl_by_page, $order_by = '') {

        if (!is_array($args)) {
            $args = array();
        }
        $offset = intval($offset);
        if ($offset <= 0) {
            $offset = 1;
        }

        $nbl_by_page = intval($nbl_by_page);
        if ($nbl_by_page <= 0) {
            $nbl_by_page = 10;
        }

        $sql = self::changeSeparator($db, $sql);

        $order_by = trim($order_by);
        if ($order_by != '') {
            $sql .= ' ORDER BY ' . $order_by;
        }

        $rows = array();

        $profiler_status = $db->getProfilerStatus();
        if ($profiler_status) {
            $profiler_start = self::getMicrotime();
            $profiler_nb_rows = 0;
        }
        try {
            $st = odbc_prepare($db->getResource(), $sql, 
                    array('cursor' => DB2_SCROLLABLE));
            if (!$st) {
                self::MyDBError($db, 'selectBlock/odbc_prepare', $sql, $args);
                $rows = null;
            } else {
                if (!odbc_execute($st, $args)) {
                    self::MyDBError($db, 'selectBlock/odbc_execute', $sql, $args);
                    $rows = null;
                } else {
                    for (
                    $tofetch = $nbl_by_page,
                    $row = odbc_fetch_array($st, $offset); 
                    $row !== false && $tofetch-- > 0; 
                    $row = odbc_fetch_array($st)
                    ) {
                        $rows [] = $row;
                        if ($profiler_status) {
                            $profiler_nb_rows++;
                        }
                    }
                }
                odbc_free_result($st);
            }
            unset($st);
            if ($profiler_status) {
                self::logProfiler($profiler_start, $sql, $args, 
                        $profiler_nb_rows, 'good');
            }

            return $rows;
        } catch (Exception $e) {
            if ($profiler_status) {
                self::logProfiler($profiler_start, $sql, $args, 
                        $profiler_nb_rows, 'bad');
            }

            self::MyDBException($db, $e, $sql, $args);
        }
    }

    public static function export2CSV($db, $sql, $args = array()) {
        $st = self::getStatement($db, $sql, $args);
        $top_header_file = true;
        $csv = '';
        $row = self::getFetchAssoc($st);
        while ($row != false) {
            if ($top_header_file) {
                $csv .= join(';', array_keys($row)) . PHP_EOL;
                $top_header_file = false;
            }
            $row2 = array();
            foreach ($row as $key => $col) {
                if (is_int($col) || is_float($col)) {
                    $row2 [] = $col;
                } else {
                    $col = str_replace(array("\n", "\r\n"), '', $col);
                    $col = str_replace(array('"', "''"), '', $col);
                    $row2 [] = '"' . trim($col) . '"';
                }
            }

            $csv .= join(';', $row2) . PHP_EOL;
            $row = self::getFetchAssoc($st);
        }
        return $csv;
    }

    public static function export2XML($db, $sql, $args = array(), 
            $tag_line = '', $gen_header = true) {
        $st = self::getStatement($db, $sql, $args);
        if ($tag_line == '') {
            $tag_line = 'row';
        }
        $tag_open = "<{$tag_line}>";
        $tag_close = "</{$tag_line}>";

        $xml = '';
        if ($gen_header === true) {
            $xml .= '<?xml version="1.0" encoding="UTF-8"?>';
        }
        $row = self::getFetchAssoc($st);
        while ($row != false) {
            $xml .= $tag_open;
            foreach ($row as $key => $col) {
                $key = trim(strtolower($key));
                if (is_int($col) || is_float($col)) {
                    $xml .= '<' . $key . '>' .
                            htmlspecialchars($col, ENT_QUOTES, "UTF-8") .
                            '</' . $key . '>';
                } else {
                    $col = str_replace(array("\n", "\r\n"), '', $col);
                    $col = str_replace(array('"', "''"), '', $col);
                    $col = trim($col);
                    if (strlen($col) > 0) {
                        $xml .= '<' . $key . '>' .
                                htmlspecialchars($col, ENT_QUOTES, "UTF-8") .
                                '</' . $key . '>';
                    } else {
                        $xml .= '<' . $key . ' />';
                    }
                }
            }

            $xml .= $tag_close;
            $row = self::getFetchAssoc($st);
        }
        return $xml;
    }

    public static function export2insertSQL($db, $sql, $args = array()) {
        $st = self::getStatement($db, $sql, $args);
        $top_header_file = true;
        $sql_insert = '';
        $row = self::getFetchAssoc($st);
        $nb_lig = 0 ;
        while ($row != false) {
            if ($nb_lig > 1000) {
                $nb_lig = 0 ;
                $top_header_file = true ;
                $sql_insert .= ';' . PHP_EOL;
            }            
            if ($top_header_file) {
                $sql_insert .= 'INSERT INTO tableDestinataire ' . PHP_EOL;
                $sql_insert .= '( ' . join(', ', array_keys($row)) . ' ) ' . PHP_EOL;
                $sql_insert .= 'VALUES ' . PHP_EOL;
                $top_header_file = false;
            }
            $row2 = array();
            $sql_insert .= '( ';
            foreach ($row as $key => $col) {
                if (is_int($col) || is_float($col)) {
                    $row2 [] = $col;
                } else {
                    /*
                     * le test ci-dessous ne sera pas pris en compte avec PDO 
                     * qui renvoie des donn�es de type string
                     *  par contre il fonctionnera avec odbc_connect qui renvoie 
                     * des donn�es correctement typ�es
                     */
                    if (is_int($col) || is_float($col)) {
                        $row2 [] = trim($col);
                    } else {
                        $col = str_replace(array("\n", "\r\n"), '', $col);
                        $col = str_replace(array("'", "''"), '', $col);
                        $col = str_replace(array('"', "''"), '', $col);
                        $row2 [] = "'" . trim($col) . "'";
                    }
                }
            }

            $sql_insert .= join(', ', $row2) . ' )';
            $row = self::getFetchAssoc($st);
            if ($row != false) {
                $sql_insert .= ',' . PHP_EOL;
            } else {
                $sql_insert .= PHP_EOL;
            }
            $nb_lig++ ;
        }
        if ($sql_insert != '') {
            $sql_insert .= ';' . PHP_EOL;
        }
        return $sql_insert;
    }

    public static function countNbRowsFromTable($db, $table, $schema = '') {
        if (trim($schema) == '') {
            $sql = 'SELECT COUNT(*) AS NB_ROWS FROM ' . trim($table);
        } else {
            $sql = 'SELECT COUNT(*) AS NB_ROWS FROM ' . trim($schema) . 
                    '{SEPARATOR}' . trim($table);
        }
        $data = self::selectOne($db, $sql, array(), true);
        if (is_array($data) && isset($data[0])) {
            return $data[0];
        } else {
            return 0;
        }
    }

    public static function countNbRowsFromSQL($db, $sql, $args = array()) {

        $sql = 'SELECT COUNT(*) AS NB_ROWS FROM (' . trim($sql) . ') AS FOO';

        $data = self::selectOne($db, $sql, $args, true);
        if (is_array($data) && isset($data[0])) {
            return $data[0];
        } else {
            return 0;
        }
    }

    /*
     * M�thode permettant de r�cup�rer le dernier ID cr�� dans la BD
     * Le dernier ID est soit l'ID interne DB2, soit une s�quence DB2 dont 
     * le code est transmis en param�tre
     */

    public static function getLastInsertId($db, $sequence = '') {
        $sequence = trim($sequence);
        if ($sequence == '') {
            $sql = "SELECT IDENTITY_VAL_LOCAL() FROM SYSIBM{SEPARATOR}SYSDUMMY1";
        } else {
            $sql = "SELECT NEXT VALUE FOR {$sequence} FROM SYSIBM{SEPARATOR}SYSDUMMY1";
        }
        $data = self::selectOne($db, $sql, array(), true);
        if (is_array($data) && isset($data[0])) {
            return $data[0];
        } else {
            return false;
        }
    }

    /*
     * M�thode permettant de v�rifier si une valeur existe bien dans une colonne
     * peut �galement �tre utilis� pour v�rifier la non existence d'une valeur
     * avant son insertion dans une table (cas des colonnes en "cl� unique"
     * par exemple
     */

    public static function valueIsExisting($db, $table, $nomcol, $valcol,
            $where_optionnel = '') {
        $where_sql = " WHERE {$nomcol} = ? ";
        if ($where_optionnel != '') {
            $where_sql .= ' and ' . $where_optionnel;
        }
        $query = "SELECT count(*) FROM {$table} {$where_sql} fetch first 1 row only";
        $data = self::selectOne($db, $query, array($valcol), true);
        if (is_array($data) && isset($data[0]) && $data[0] == 1) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * M�thode permettant de v�rifier si une valeur existe bien dans une colonne
     * mais sur une autre ligne que la ligne en cours de traitement
     * on peut l'utiliser par exemple en modification d'enregistrement, pour
     * emp�cher qu'un code existant sur une autre ligne ne puisse �tre utilis�
     * sur la ligne en cours de modification.
     */

    public static function valueIsExistingOnOtherRecord($db, $table, $nomcol, 
            $valcol, $idencours, $where_optionnel = '') {
        $where_sql = " WHERE {$nomcol} = ? and id <> ? ";
        if ($where_optionnel != '') {
            $where_sql .= ' and ' . $where_optionnel;
        }
        $query = "SELECT count(*) FROM {$table} {$where_sql} fetch first 1 row only ";
        $data = self::selectOne($db, $query, array($valcol, $idencours), true);
        if (is_array($data) && isset($data[0]) && $data[0] == 1) {
            return true;
        } else {
            return false;
        }
    }
    public static function getInfoDatabase($db) {
        $infos = array();

        return $infos;
    }

    /**
     * fonction utilis�e avec la classe DBException permettant un meilleur
     * contr�le sur la pr�sentation des erreurs renvoy�es par cette classe
     * @param type $db
     * @param type $fonction_db2
     * @param type $reqsql
     * @param type $args 
     */
    private static function MyDBError($db, $fonction_db2, $reqsql, $args = array()) {
        $getmessage = 'Erreur sur ' . $fonction_db2 . ' : ' . PHP_EOL;
        $getmessage .= 'DB2 Error Code : ' . odbc_error($db) . ' // ' . PHP_EOL;
        $getmessage .= 'DB2 Error Msg : ' . odbc_errormsg($db) . ' // ' . PHP_EOL;
        ob_start();
        var_dump($args);
        $dump_args = ob_get_clean();
        if (isset($GLOBALS['sixaxe_sql_debug']) && $GLOBALS['sixaxe_sql_debug'] === true) {
            echo "<table border=\"1\">
       <tr>
        <td> Code SQL </td>
        <td> {$reqsql} </td>
       </tr>
       <tr>
        <td> Msg  </td>
        <td> {$getmessage} </td>
       </tr>
       <tr>
        <td> Arguments  </td>
        <td> {$dump_args} </td>
       </tr>
       
       </table><br />";
        }
        error_log("code SQL   -> " . $reqsql);
        error_log("getMessage -> " . $getmessage);
        error_log("arguments -> " . $dump_args);
    }

    /**
     * Fonction utilis�e avec la classe DBException permettant un meilleur
     * contr�le sur la pr�sentation des erreurs renvoy�es par cette classe
     * @param type $db
     * @param type $DBexc
     * @param type $reqsql
     * @param type $args 
     */
    public static function MyDBException($db, $DBexc, $reqsql = '', $args = array()) {

        $tab_log = array();
        $tab_log ['SQL_code'] = $reqsql;
        $tab_log ['Message'] = $DBexc->getMessage();
        $tab_log ['Trace'] = $DBexc->getTraceAsString();
        $tab_log ['Code'] = $DBexc->getCode();
        $tab_log ['File'] = $DBexc->getFile();
        $tab_log ['Line'] = $DBexc->getLine();
        if (is_array($args) && count($args) > 0) {
            $tab_log ['Arguments'] = var_export($args, true);
        } else {
            $tab_log ['Arguments'] = '';
        }

        if (defined('DEBUG_MODE') && DEBUG_MODE == true) {
            self::message2Log($tab_log, true);
        } else {
            self::message2Log($tab_log, false);
        }
    }

    /**
     * R�cup�ration microtime pour
     * @return number
     */
    protected static function getMicrotime() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float) $usec + (float) $sec);
    }

    /**
     * Alimentation de la log avec le Profiler de requ�te SQL
     * @param type $profiler_start
     * @param type $sql
     * @param type $args
     * @param type $nb_rows
     * @param type $query_status 
     */
    protected static function logProfiler($profiler_start, $sql, $args, 
            $nb_rows, $query_status) {
        $profiler_stop = self::getMicrotime();
        $total_time = $profiler_stop - $profiler_start;
        $profiler = array('Profiler type' => 'SQL', 'time' => $total_time,
            'SQL' => $sql, 'Arguments' => $args, 'nb_rows' => $nb_rows, 
            'Status' => $query_status);
        self::message2Log($profiler, false);
    }

    protected static function message2Log($var, $display = false) {
        /*
         * envoi syst�matique dans la log
         */
        ob_start();
        var_dump($var);
        $dump = ob_get_clean();
        error_log($dump);

        /*
         * affichage dans le flux html uniquement si demand�
         */
        if ($display) {
            echo '<pre>' . $dump . '</pre>';
        }
    }

}

