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
 * @version    DBWrapperClassStd.php 2012-03-28 09:15:47
 *
 * Classe fournissant un jeu de méthodes communes aux différents Wrapper 
 * orientés sur PDO
 */
require_once 'DBWrapperInterface.php';

abstract class DBWrapperClassStd implements DBWrapperInterface {

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
            if (trim($args) > 0) {
                /*
                 * si $args n'est pas de type "array" alors on le transforme
                 * en type "array" car il s'agit probablement d'un oubli du
                 * développeur
                 */
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
            $res = $db->getResource();
            $st = $res->prepare($sql);
            $ok = $st->execute($args);
            if ($ok) {
                /*
                 * par défaut c'est le mode "fetch array" qui est utilisé, 
                 * mais dans certains cas le mode "fetch column" peut être utile
                 * notamment quand on ne souhaite récupérer qu'une seule colonne
                 */
                if ($fetch_mode_num === true) {
                    $result = $st->fetch(PDO::FETCH_NUM);
                } else {
                    $result = $st->fetch(PDO::FETCH_ASSOC);
                }
            } else {
                $result = null;
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
            if (trim($args) > 0) {
                /*
                 * si $args n'est pas de type "array" alors on le transforme
                 * en type "array" car il s'agit probablement d'un oubli du
                 * développeur
                 */
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
            $res = $db->getResource();
            $st = $res->prepare($sql);
            $ok = $st->execute($args);
            if ($ok) {
                $row = $st->fetch(PDO::FETCH_ASSOC);
                while ($row != false) {
                    $rows [] = $row;
                    $row = $st->fetch(PDO::FETCH_ASSOC);
                    if ($profiler_status) {
                        $profiler_nb_rows++;
                    }
                }
            } else {
                $rows = null;
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
            if (trim($args) > 0) {
                /*
                 * si $args n'est pas de type "array" alors on le transforme
                 * en type "array" car il s'agit probablement d'un oubli du
                 * développeur
                 */
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
            $res = $db->getResource();
            $st = $res->prepare($sql);
            $ok = $st->execute($args);
            if ($ok) {
                $rows = $st->fetchall(PDO::FETCH_KEY_PAIR);
            } else {
                $rows = null;
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
            if (trim($args) > 0) {
                /*
                 * si $args n'est pas de type "array" alors on le transforme
                 * en type "array" car il s'agit probablement d'un oubli du
                 * dÃ©veloppeur
                 */
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
            $res = $db->getResource();
            $st = $res->prepare($sql);
            $ok = $st->execute($args);
            if ($ok && $count_nb_rows === true) {
                $nbrows = $st->rowcount();
            } else {
                $nbrows = 0;
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
        return self::executeCommand($db, $cmd2, array(), false);
    }

    static function callProcedure($db, $proc_name, $proc_schema, 
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
            foreach ($args as $arg) {
                $jokers [] = '?';
            }
            $sql .= ' ( ' . implode(', ', $jokers) . ' ) ';
        }


        $profiler_status = $db->getProfilerStatus();
        if ($profiler_status) {
            $profiler_start = self::getMicrotime();
            $profiler_nb_rows = 0;
        }
        try {
            $resultset = array();

            $res = $db->getResource();
            $st = $res->prepare($sql);
            $args_inc = 0;
            $args_val = array();
            foreach ($args as $key => $arg) {
                $args_val[] = $arg['value'] ;
                $args_inc++;
                $arg['type'] = isset($arg['type'])?strtolower($arg['type']):'in';
                $tmp_key = $key;
                switch ($arg['type']) {
                    case 'out': {
                            $st->bindParam($args_inc, $tmp_key, 
                                    PDO::PARAM_STR, 4000);
                            break;
                        }
                    case 'inout': {
                            $st->bindParam($args_inc, $tmp_key, 
                                    PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 4000);
                            break;
                        }
                    default: {
                            $st->bindParam($args_inc, $tmp_key);
                        }
                }
            }
            $ok = $st->execute($args_val);
            if ($ok) {
                if ($return_resultset === true) {
                    do {
                        $row_data = $st->fetchAll(PDO::FETCH_ASSOC);
                        if ($row_data) {
                            foreach ($row_data as $data) {
                                $resultset [] = $data;
                            }
                        }
                    } while ($st->nextRowset());
                }
            }
            unset($st);
            if ($profiler_status) {
                $profiler_nb_rows = count($resultset);
                self::logProfiler($profiler_start, $sql, $args, $profiler_nb_rows, 'good');
            }

            return $resultset;
        } catch (Exception $e) {
            if ($profiler_status) {
                self::logProfiler($profiler_start, $sql, $args, $profiler_nb_rows, 'bad');
            }

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
            $res = $db->getResource();
            $st = $res->prepare($sql);
            $ok = $st->execute($args);
            if (!$ok) {
                $st = false;
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
            return $st->fetch(PDO::FETCH_ASSOC);
        }
    }

    /*
     * Méthode permettant de récupérer le dernier ID créé dans la BD
     * Méthode à réécrire dans chaque classe fille, la technique d'incrémentation 
     * étant spécifique à chaque base de données
     */

    public static function getLastInsertId($db, $sequence = '') {
        return null;
    }

    /*
     * Méthode à redéfinir dans chaque classe fille, la technique de pagination 
     * étant différente selon la base de données utilisée
     */

    public static function getPagination($db, $sql, $args, $offset, $nbl_by_page, $order_by = '') {
        return self::getScrollCursor($db, $sql, $args, $offset, $nbl_by_page, $order_by);
    }

    /*
     * Pagination via la technique du Scroll Cursor telle qu'elle est implémentée 
     * dans PDO
     */
    public static function getScrollCursor($db, $sql, $args, $offset, $nbl_by_page, $order_by = '') {

        if (!is_array($args)) {
            $args = array();
        }
        $offset = intval($offset);
        if ($offset <= 0) {
            $offset = 1;
        }
        /*
         * L'affichage doit démarrer sur l'offset -1, sinon on "rate" la première ligne
         */
        $offset--;

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
            $res = $db->getResource();
            $st = $res->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
            $st->execute($args);

            if ($offset > 0) {
                /*
                 * Un bug d'origine inconnu oblige à effectuer un premier 
                 * positionnement 
                 * sur la ligne n° 0, quand on affiche les offsets > 0
                 * Dans le cas où on affiche l'offset 0, il ne faut surtout pas faire
                 * ce premier positionnement, car il interfère avec celui qui est
                 * effecuté par la boucle d'affichage (for).
                 */
                $lost = $st->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_REL, 0);
            }

            for (
            $tofetch = $nbl_by_page,
            $row = $st->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_REL, $offset); 
            $row !== false && $tofetch-- > 0; 
            $row = $st->fetch(PDO::FETCH_ASSOC)
            ) {
                $rows [] = $row;
                if ($profiler_status) {
                    $profiler_nb_rows++;
                }
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
                     * qui renvoie des données de type string,
                     * par contre il fonctionnera avec DB2_Connect qui renvoie 
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

    public static function valueIsExisting($db, $table, $nomcol, $valcol, 
            $where_optionnel = '') {
        $where_sql = " WHERE {$nomcol} = ? ";
        if ($where_optionnel != '') {
            $where_sql .= ' and ' . $where_optionnel;
        }
        $query = "SELECT '1' as good FROM {$table} {$where_sql} limit 1";
        $data = self::selectOne($db, $query, array($valcol), true);
        if (is_array($data) && isset($data[0]) && $data[0] == '1') {
            return true;
        } else {
            return false;
        }
    }

    public static function valueIsExistingOnOtherRecord($db, $table, $nomcol, 
            $valcol, $idencours, $where_optionnel = '') {
        $where_sql = " WHERE {$nomcol} = ? and id <> ? ";
        if ($where_optionnel != '') {
            $where_sql .= ' and ' . $where_optionnel;
        }
        $query = "SELECT '1' as good FROM {$table} {$where_sql} limit 1 ";
        $data = self::selectOne($db, $query, array($valcol, $idencours), true);
        if (is_array($data) && isset($data[0]) && $data[0] == '1') {
            return true;
        } else {
            return false;
        }
    }

    public static function getInfoDatabase($db) {

        $result = array();

        if ($db instanceof PDO) {
            /*
             * TODO : valeurs ci-dessous provenant de DB2, fournies ici en 
             * exemple, liste à adapter à chaque base de données
             */
            $attributes = array(
                "ERRMODE", "CASE", "CLIENT_VERSION", "DRIVER_NAME", "ORACLE_NULLS", "PERSISTENT"
            );

            foreach ($attributes as $val) {
                $result["PDO::ATTR_$val"] = $db->getAttribute(constant("PDO::ATTR_$val"));
            }
        }
        return $result;
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
     * @param float $profiler_start
     * @param string $sql
     * @param array $args
     */
    protected static function logProfiler($profiler_start, $sql, $args, $nb_rows, $query_status) {
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

