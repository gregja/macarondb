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
 * Wrapper Base de Données dédié au connecteur DB2_Connect
 */
require_once 'DBWrapperInterface.php';

abstract class DB2_IBMi_DBWrapper implements DBWrapperInterface {
    /*
     * La fonction db2_connect permet l'utilisation de la syntaxe IBM avec 
     * le "/" au lieu du "."
     * comme séparateur entre nom de bibliothèque et nom de table. Donc pour 
     * savoir si le "/" ou le "."
     * doit être utilisé, on se base sur le contenu du paramètre "i5_naming" 
     * défini dans les options de db2_connect.
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
            $st = db2_prepare($db->getResource(), $sql);
            if (!$st) {
                self::MyDBError($db, 'selectOne/db2_prepare', $sql, $args);
                $result = null;
            } else {
                if (!db2_execute($st, $args)) {
                    self::MyDBError($db, 'selectOne/db2_execute', $sql, $args);
                    $result = null;
                } else {
                    /*
                     * par défaut c'est le mode "fetch array" qui est utilisé, 
                     * mais dans certains cas le mode "fetch num" peut être utile
                     */
                    if ($fetch_mode_num === true) {
                        $result = db2_fetch_array($st);
                    } else {
                        $result = db2_fetch_assoc($st);
                    }
                }
                db2_free_stmt($st);
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
            $st = db2_prepare($db->getResource(), $sql);
            if (!$st) {
                self::MyDBError($db, 'selectBlock/db2_prepare', $sql, $args);
                $rows = null;
            } else {
                if (!db2_execute($st, $args)) {
                    self::MyDBError($db, 'selectBlock/db2_execute', $sql, $args);
                    $rows = null;
                } else {
                    $row = db2_fetch_assoc($st);
                    while ($row != false) {
                        $rows [] = $row;
                        $row = db2_fetch_assoc($st);
                        if ($profiler_status) {
                            $profiler_nb_rows++;
                        }
                    }
                }
                db2_free_stmt($st);
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

            $st = db2_prepare($db->getResource(), $sql);
            if (!$st) {
                self::MyDBError($db, 'selectBlock/db2_prepare', $sql, $args);
                $rows = null;
            } else {
                if (!db2_execute($st, $args)) {
                    self::MyDBError($db, 'selectBlock/db2_execute', $sql, $args);
                    $rows = null;
                } else {
                    $row = db2_fetch_array($st);
                    while ($row != false) {
                        $rows [$row[0]] = $row[1];
                        $row = db2_fetch_array($st);
                    }
                }
                db2_free_stmt($st);
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

            $st = db2_prepare($db->getResource(), $sql);
            if (!$st) {
                self::MyDBError($db, 'executeCommand/db2_prepare', $sql, $args);
                $nbrows = null;
            } else {
                if (!db2_execute($st, $args)) {
                    self::MyDBError($db, 'executeCommand/db2_execute', $sql, $args);
                    $nbrows = null;
                } else {
                    if ($count_nb_rows === true) {
                        $nbrows = db2_num_rows($st);
                    }
                }
                db2_free_stmt($st);
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

            $st = db2_prepare($db->getResource(), $sql);
            if (!$st) {
                self::MyDBError($db, 'executeCommand/db2_prepare', $sql, $args);
            } else {
                $args_inc = 0;
                foreach ($args as $key => $arg) {
                    /*
                     * crée artificiellement une variable ayant pour nom 
                     * le contenu de la variable $key
                     */
                    $$key = $args[$key]['value'];
                    $args_inc++;
                    $arg['type'] = isset($arg['type'])?strtolower($arg['type']):'in';

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
                if (!db2_execute($st)) {
                    self::MyDBError($db, 'executeCommand/db2_execute', $sql, $args);
                } else {
                    foreach ($args as $key => $arg) {
                        $arg['type'] = strtolower($arg['type']);
                        if ($arg['type'] == 'out' || $arg['type'] == 'inout') {
                            $args[$key]['value'] = $$key;
                        }
                    }
                    if ($return_resultset === true) {
                        $row = db2_fetch_assoc($st);
                        while ($row != false) {
                            $resultset [] = $row;
                            $row = db2_fetch_assoc($st);
                        }
                    }
                }
                db2_free_stmt($st);
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

            $st = db2_prepare($db->getResource(), $sql);
            if (!$st) {
                self::MyDBError($db, 'getStatement/db2_prepare', $sql, $args);
            } else {
                if (!db2_execute($st, $args)) {
                    self::MyDBError($db, 'getStatement/db2_execute', $sql, $args);
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
            return db2_fetch_assoc($st);
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
        // on recherche la position du 1er SELECT pour le compléter
        $pos = stripos($sql, 'select');
        if ($pos !== false) {
            $temp = 'select row_number() over (' . $order_by . ') as rn, ';
            $sql = substr_replace($sql, $temp, $pos, 6);
        } else {
            // pagination impossible si requête ne contient pas un SELECT
            return false;
        }
        $sql = <<<BLOC_SQL
select foo.* from (  
{$sql}
) as foo  
where foo.rn between ? and ? 
BLOC_SQL;
        /*
         * Ajout des paramètres du "between" dans le tableau des arguments 
         * transmis à la requête
         */
        $args [] = $offset;
        $args [] = $limit_max;

        return self::selectBlock($db, $sql, $args);
    }

    /*
     * Pagination via la technique du Scroll Cursor telle qu'elle est 
     * implémentée dans db2_connect
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
            $st = db2_prepare($db->getResource(), $sql, 
                    array('cursor' => DB2_SCROLLABLE));
            if (!$st) {
                self::MyDBError($db, 'selectBlock/db2_prepare', $sql, $args);
                $rows = null;
            } else {
                if (!db2_execute($st, $args)) {
                    self::MyDBError($db, 'selectBlock/db2_execute', $sql, $args);
                    $rows = null;
                } else {
                    for (
                    $tofetch = $nbl_by_page,
                    $row = db2_fetch_assoc($st, $offset); 
                    $row !== false && $tofetch-- > 0; 
                    $row = db2_fetch_assoc($st)
                    ) {
                        $rows [] = $row;
                        if ($profiler_status) {
                            $profiler_nb_rows++;
                        }
                    }
                }
                db2_free_stmt($st);
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
                     * qui renvoie des données de type string
                     *  par contre il fonctionnera avec DB2_Connect qui renvoie 
                     * des données correctement typées
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
     * Méthode permettant de récupérer le dernier ID créé dans la BD
     * Le dernier ID est soit l'ID interne DB2, soit une séquence DB2 dont 
     * le code est transmis en paramètre
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
     * Méthode permettant de vérifier si une valeur existe bien dans une colonne
     * peut également être utilisé pour vérifier la non existence d'une valeur
     * avant son insertion dans une table (cas des colonnes en "clé unique"
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
     * Méthode permettant de vérifier si une valeur existe bien dans une colonne
     * mais sur une autre ligne que la ligne en cours de traitement
     * on peut l'utiliser par exemple en modification d'enregistrement, pour
     * empêcher qu'un code existant sur une autre ligne ne puisse être utilisé
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
        $client = db2_client_info($db->getResource());
        $infos = array();
        if ($client) {
            $key = 'DB2_CLIENT_INFO';
            $infos[$key]['DRIVER_NAME'] = $client->DRIVER_NAME;
            $infos[$key]['DRIVER_VER'] = $client->DRIVER_VER;
            $infos[$key]['DATA_SOURCE_NAME'] = $client->DATA_SOURCE_NAME;
            $infos[$key]['DRIVER_ODBC_VER'] = $client->DRIVER_ODBC_VER;
            $infos[$key]['ODBC_VER'] = $client->ODBC_VER;
            $infos[$key]['ODBC_SQL_CONFORMANCE'] = $client->ODBC_SQL_CONFORMANCE;
            $infos[$key]['APPL_CODEPAGE'] = $client->APPL_CODEPAGE;
            $infos[$key]['CONN_CODEPAGE'] = $client->CONN_CODEPAGE;
        }
        $server = db2_server_info($db->getResource());
        if ($server) {
            $key = 'DB2_SERVER_INFO';
            $infos[$key]['DBMS_NAME'] = $server->DBMS_NAME;
            $infos[$key]['DBMS_VER'] = $server->DBMS_VER;
            $infos[$key]['DB_CODEPAGE'] = $server->DB_CODEPAGE;
            $infos[$key]['DB_NAME'] = $server->DB_NAME;
            $infos[$key]['INST_NAME'] = $server->INST_NAME;
            $infos[$key]['SPECIAL_CHARS'] = $server->SPECIAL_CHARS;
            $infos[$key]['KEYWORDS'] = $server->KEYWORDS;
            $infos[$key]['DFT_ISOLATION'] = $server->DFT_ISOLATION;
            $il = array();
            foreach ($server->ISOLATION_OPTION as $opt) {
                $il [] = $opt;
            }
            $infos[$key]['ISOLATION_OPTION'] = implode(' ', $il);
            $infos[$key]['SQL_CONFORMANCE'] = $server->SQL_CONFORMANCE;
            $infos[$key]['PROCEDURES'] = $server->PROCEDURES;
            $infos[$key]['IDENTIFIER_QUOTE_CHAR'] = $server->IDENTIFIER_QUOTE_CHAR;
            $infos[$key]['LIKE_ESCAPE_CLAUSE'] = $server->LIKE_ESCAPE_CLAUSE;
            $infos[$key]['MAX_COL_NAME_LEN'] = $server->MAX_COL_NAME_LEN;
            $infos[$key]['MAX_ROW_SIZE'] = $server->MAX_ROW_SIZE;
            $infos[$key]['MAX_IDENTIFIER_LEN'] = $server->MAX_IDENTIFIER_LEN;
            $infos[$key]['MAX_INDEX_SIZE'] = $server->MAX_INDEX_SIZE;
            $infos[$key]['MAX_PROC_NAME_LEN'] = $server->MAX_PROC_NAME_LEN;
            $infos[$key]['MAX_SCHEMA_NAME_LEN'] = $server->MAX_SCHEMA_NAME_LEN;
            $infos[$key]['MAX_STATEMENT_LEN'] = $server->MAX_STATEMENT_LEN;
            $infos[$key]['MAX_TABLE_NAME_LEN'] = $server->MAX_TABLE_NAME_LEN;
            $infos[$key]['NON_NULLABLE_COLUMNS'] = $server->NON_NULLABLE_COLUMNS;
        }

        return $infos;
    }

    /**
     * fonction utilisée avec la classe DBException permettant un meilleur
     * contrôle sur la présentation des erreurs renvoyées par cette classe
     * @param type $db
     * @param type $fonction_db2
     * @param type $reqsql
     * @param type $args 
     */
    private static function MyDBError($db, $fonction_db2, $reqsql, $args = array()) {
        $getmessage = 'Erreur sur ' . $fonction_db2 . ' : ' . PHP_EOL;
        $getmessage .= 'DB2 Error Code : ' . db2_stmt_error() . ' // ' . PHP_EOL;
        $getmessage .= 'DB2 Error Msg : ' . db2_stmt_errormsg() . ' // ' . PHP_EOL;
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
     * Fonction utilisée avec la classe DBException permettant un meilleur
     * contrôle sur la présentation des erreurs renvoyées par cette classe
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
     * Récupération microtime pour
     * @return number
     */
    protected static function getMicrotime() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float) $usec + (float) $sec);
    }

    /**
     * Alimentation de la log avec le Profiler de requête SQL
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
         * envoi systématique dans la log
         */
        ob_start();
        var_dump($var);
        $dump = ob_get_clean();
        error_log($dump);

        /*
         * affichage dans le flux html uniquement si demandé
         */
        if ($display) {
            echo '<pre>' . $dump . '</pre>';
        }
    }

}

