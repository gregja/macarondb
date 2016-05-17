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
class ODBC_IBMi_DBConnex {

    protected static $_instance;
    protected static $_liblist = array();
    protected static $_dsn;
    protected static $_sql_separator = '.';

    private function __construct() {
        
    }

    public static function getInstance($system, $user, $password, $options = array(), $persistent = false) {

        $cursor_type = SQL_CUR_USE_DRIVER;

        if (is_array($options) && count($options) > 0) {
            $options = self::generate_config($options);
        }

        $is_IBMi = (php_uname('s') == 'OS400' || PHP_OS == "AIX" || PHP_OS == "OS400") ? true : false;

        if ($is_IBMi) {
            $dsn = '*LOCAL';
        } else {
            $dsn = 'DRIVER={iSeries Access ODBC Driver};SYSTEM=' . $system;

            // Attention à ne pas ajouter de ";" inutile à la fin d'un DSN
            // car PDO n'apprécie pas, et odbc_connect probablement pas non plus
            $dsn_temp = self::generate_dsn($options);
            if ($dsn_temp != '') {
                $dsn .= ';' . $dsn_temp;
            }
        }

        if ($persistent === true) {
            self::$_instance = odbc_pconnect($dsn, $user, $password);
        } else {
            self::$_instance = odbc_connect($dsn, $user, $password, $cursor_type);
        }
        if (!is_resource(self::$_instance)) {
            Throw new Exception('Erreur sur Connexion DB2 : ' . odbc_error() . ' ' . odbc_errormsg());
        } else {
            if ($is_IBMi) {
                $temp_options = self::generate_odbc_options($options);
                if (count($temp_options) > 0) {
                    /*
                     * Les paramètres ne pouvant être transmis via DSN dans ce mode
                     * on doit les transmettre via la fonction odbc_setoption()
                     */
                    foreach ($temp_options as $option_key => $option_val) {
                        odbc_setoption(self::$_instance, 1, $option_key, $option_val);
                    }
                }
                if (count(self::$_liblist) > 0) {
                    $tmp_separator = self::$_sql_separator ;
                    foreach (self::$_liblist as $lib_val) {
                        $lg = '00000000' . number_format(strlen($lib_val) + 9, 5, ".", "");
                        $addlible = "call qsys{$tmp_separator}qcmdexc ('ADDLIBLE " . $lib_val . "' , $lg)";
                        $result = odbc_exec(self::$_instance, $addlible);
                        if ($result == 0) {
                            error_log("odbc_connect : Erreur / ADDLIBLE " . 
                                    $lib_val . " / Erreur SQL : " . odbc_error() 
                                    . " ; " . odbc_errormsg());
                        }
                    }
                }
            }
        }

        return self::$_instance;
    }

    /*
     * Paramètres de connexion DB2 i5 transformés en DSN 
     * Source documentaire pour PDO :
     *  http://publib.boulder.ibm.com/infocenter/iseries/v5r4/index.jsp?topic=%2Frzaik%2Fconnectkeywords.htm
     * Source documentaire pour ODBC :
     *  http://publib.boulder.ibm.com/infocenter/iseries/v5r4/index.jsp?topic=%2Fcli%2Frzadpfnsconx.htm
     * Source documentaire pour DB2_Connect :
     *  http://fr2.php.net/manual/fr/function.db2-connect.php
     */

    public static function generate_config($options = array()) {

        if (!is_array($options)) {
            $options = array();
        }
        /*
         * si $options ['i5_libl'] est un tableau, alors on le transforme en chaîne (postes séparés par un blanc pour odbc_connect)
         * tant qu'on ne sait pas si $options ['i5_libl'] est un tableau contenant plus d'une bibliothèque, on considère que
         * c'est le mode SQL_NAMING_OFF qui doit être retenu comme option par défaut (syntaxe full SQL)
         */

        if (isset($options ['i5_libl'])) {
            // tableau à transformer en chaîne de caractères si nécessaire + forçage de la syntaxe IBM
            if (is_array($options ['i5_libl'])) {
                $options ['i5_libl'] = implode(' ', $options ['i5_libl']);
            }
        }

        /*

          Valeurs définies pour le niveau d'isolation, pour le connecteur DB2_Connect :
          SQL_TXN_NO_COMMIT = 1
          SQL_TXN_READ_UNCOMMITTED = 2
          SQL_TXN_READ_COMMITTED = 3
          SQL_TXN_REPEATABLE_READ = 4
          SQL_TXN_SERIALIZABLE = 5

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
        if (isset($options ['i5_commit'])) {
            // si valeur déjà de type numérique entier, alors on la considère comme valide et on la prend telle quelle
            if (!is_int($options ['i5_commit'])) {
                $options ['i5_commit'] = strtoupper($options ['i5_commit']);
                switch ($options ['i5_commit']) {
                    // *NC (No Commit)
                    case '*NONE' :
                    case '*NC' :
                    case 'NO_COMMIT': {
                            $options ['i5_commit'] = SQL_TXN_NO_COMMIT;
                            break;
                        }
                    // *UR (Uncommitted Read)
                    case '*UR' :
                    case '*CHG' :
                    case 'READ_UNCOMMITTED' : {
                            $options ['i5_commit'] = SQL_TXN_READ_UNCOMMITTED;
                            break;
                        }
                    // *RR (Repeatable Read)
                    case '*RR' :
                    case '*ALL' :
                    case 'REPEATABLE_READ' : {
                            $options ['i5_commit'] = SQL_TXN_REPEATABLE_READ;
                            break;
                        }
                    // *CS (Read committed) 
                    case '*CS' :
                    case 'READ_COMMITTED' : {
                            $options ['i5_commit'] = SQL_TXN_READ_COMMITTED;
                            break;
                        }
                    // *RR (Serializable)
                    case '*RR' :
                    case 'SERIALIZABLE' : {
                            $options ['i5_commit'] = SQL_TXN_SERIALIZABLE;
                            break;
                        }
                    default: {
                            // Mode NC par défaut
                            $options ['i5_commit'] = SQL_TXN_NO_COMMIT;
                        }
                }
            }
        }

        /*
         * Formats des données temporelles pour DB2_Connect :
          SQL_FMT_ISO = 1
          SQL_FMT_USA = 2
          SQL_FMT_EUR = 3
          SQL_FMT_JIS = 4
          SQL_FMT_MDY = 5
          SQL_FMT_DMY = 6
          SQL_FMT_YMD = 7
          SQL_FMT_JUL = 8
          SQL_FMT_JOB = 10
         */
        if (isset($options ['i5_date_fmt'])) {
            // si valeur déjà de type numérique entier, alors on la considère comme valide et on la prend telle quelle
            // sinon on convertit la chaîne dans la valeur numérique correspondante
            if (!is_int($options ['i5_date_fmt'])) {
                $options ['i5_date_fmt'] = strtoupper($options ['i5_date_fmt']);
                switch ($options ['i5_date_fmt']) {
                    case '*ISO': {
                            $options ['i5_date_fmt'] = SQL_FMT_ISO;
                            break;
                        }
                    case '*EUR': {
                            $options ['i5_date_fmt'] = SQL_FMT_EUR;
                            break;
                        }
                    case '*JOB': {
                            $options ['i5_date_fmt'] = SQL_FMT_JOB;
                            break;
                        }
                    case '*DMY': {
                            $options ['i5_date_fmt'] = SQL_FMT_DMY;
                            break;
                        }
                    case '*YMD': {
                            $options ['i5_date_fmt'] = SQL_FMT_YMD;
                            break;
                        }
                    case '*MDY': {
                            $options ['i5_date_fmt'] = SQL_FMT_MDY;
                            break;
                        }
                    case '*USA': {
                            $options ['i5_date_fmt'] = SQL_FMT_USA;
                            break;
                        }
                    case '*JIS': {
                            $options ['i5_date_fmt'] = SQL_FMT_JIS;
                            break;
                        }
                    case '*JUL': {
                            $options ['i5_date_fmt'] = SQL_FMT_JUL;
                            break;
                        }
                    default: {
                            // Format ISO par défaut
                            $options ['i5_date_fmt'] = SQL_FMT_ISO;
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
                            $options ['i5_time_fmt'] = SQL_FMT_ISO;
                            break;
                        }
                    case '*EUR': {
                            $options ['i5_time_fmt'] = SQL_FMT_EUR;
                            break;
                        }
                    case '*JOB': {
                            $options ['i5_time_fmt'] = SQL_FMT_JOB;
                            break;
                        }
                    case '*DMY': {
                            $options ['i5_time_fmt'] = SQL_FMT_DMY;
                            break;
                        }
                    case '*YMD': {
                            $options ['i5_time_fmt'] = SQL_FMT_YMD;
                            break;
                        }
                    case '*MDY': {
                            $options ['i5_time_fmt'] = SQL_FMT_MDY;
                            break;
                        }
                    case '*USA': {
                            $options ['i5_time_fmt'] = SQL_FMT_USA;
                            break;
                        }
                    case '*JIS': {
                            $options ['i5_time_fmt'] = SQL_FMT_JIS;
                            break;
                        }
                    case '*JUL': {
                            $options ['i5_time_fmt'] = SQL_FMT_JUL;
                            break;
                        }
                    case '*HMS': {
                            $options ['i5_time_fmt'] = SQL_FMT_HMS;
                            break;
                        }
                    default: {
                            // Format ISO par défaut
                            $options ['i5_time_fmt'] = SQL_FMT_ISO;
                        }
                }
            }
        }
        /*
         * Format du séparateur de décimale pour DB2_Connect :
          SQL_SEP_SLASH = 1
          SQL_SEP_DASH = 2
          SQL_SEP_PERIOD = 3
          SQL_SEP_COMMA = 4
          SQL_SEP_BLANK = 5
          SQL_SEP_COLON = 6
          SQL_SEP_JOB = 7
         */
        if (isset($options ['i5_decimal_sep'])) {
            // si valeur déjà de type numérique entier, alors on la considère comme valide et on la prend telle quelle
            // sinon on convertit la chaîne dans la valeur numérique correspondante
            if (!is_int($options ['i5_decimal_sep'])) {
                $options ['i5_decimal_sep'] = strtoupper($options ['i5_decimal_sep']);
                switch ($options ['i5_decimal_sep']) {
                    case '*SLASH': {
                            $options ['i5_decimal_sep'] = SQL_SEP_SLASH;
                            break;
                        }
                    case '*DASH': {
                            $options ['i5_decimal_sep'] = SQL_SEP_DASH;
                            break;
                        }
                    case '*PERIOD': {
                            $options ['i5_decimal_sep'] = SQL_SEP_PERIOD;
                            break;
                        }
                    case '*COMMA': {
                            $options ['i5_decimal_sep'] = SQL_SEP_COMMA;
                            break;
                        }
                    case '*BLANK': {
                            $options ['i5_decimal_sep'] = SQL_SEP_BLANK;
                            break;
                        }
                    case '*COLON': {
                            $options ['i5_decimal_sep'] = SQL_SEP_COLON;
                            break;
                        }
                    case '*JOB': {
                            $options ['i5_decimal_sep'] = SQL_SEP_JOB;
                            break;
                        }
                    default: {
                            // Séparateur par défaut
                            $options ['i5_decimal_sep'] = SQL_SEP_PERIOD;
                        }
                }
            }
        }
        if (!isset($options ['DB2_ATTR_CASE']) && !isset($options ['db2_attr_case'])) {
            $options ['DB2_ATTR_CASE'] = DB2_CASE_UPPER; // result set avec noms de colonnes en majuscules par défaut
        } else {
            // si les 2 postes ont été créés, c'est une erreur, un peu de ménage s'impose
            if (isset($options ['DB2_ATTR_CASE']) && isset($options ['db2_attr_case'])) {
                unset($options ['db2_attr_case']);
            } else {
                // si le poste a été créé en minuscule, on le recrée en majuscule
                if (!isset($options ['DB2_ATTR_CASE']) && isset($options ['db2_attr_case'])) {
                    $options ['DB2_ATTR_CASE'] = $options ['db2_attr_case'];
                    unset($options ['db2_attr_case']);
                }
            }
            // les 3 valeurs possibles sont UPPER, LOWER et NATURAL, donc peu importe la manière dont ces valeurs ont été saisies
            // (exemples : DB2_CASE_UPPER, CASE_UPPER, *UPPER, ou UPPER, en majuscules ou minuscules), on normalise les valeurs à 
            // UPPER, LOWER et NATURAL, pour faciliter leur transmission à odbc_connect.
            $search_attr_case = stripos('UPPER', $options ['DB2_ATTR_CASE']);
            if ($search_attr_case !== false) {
                $options ['DB2_ATTR_CASE'] = SQL_CASE_UPPER;
            } else {
                $search_attr_case = stripos('LOWER', $options ['DB2_ATTR_CASE']);
                if ($search_attr_case !== false) {
                    $options ['DB2_ATTR_CASE'] = SQL_CASE_LOWER;
                } else {
                    $search_attr_case = stripos('NATURAL', $options ['DB2_ATTR_CASE']);
                    if ($search_attr_case !== false) {
                        $options ['DB2_ATTR_CASE'] = SQL_CASE_NATURAL;
                    } else {
                        $options ['DB2_ATTR_CASE'] = SQL_CASE_UPPER;
                    }
                }
            }
        }

        return $options;
    }

    protected static function generate_dsn($options) {
        $array_dsn = array();

        if (isset($options ['i5_naming']) && $options ['i5_naming'] == true) {
            $array_dsn [] = 'NAM=1';
            self::$_sql_separator = '/';
        } else {
            $array_dsn [] = 'NAM=0';
        }
        if (isset($options ['i5_libl']) && $options ['i5_libl'] != '') {
            $array_dsn [] = 'DBQ=' . $options ['i5_libl'];
        }
        if (isset($options ['i5_lib']) && $options ['i5_lib'] != '') {
            $array_dsn [] = 'DATABASE=' . $options ['i5_lib'];
        }
        if (isset($options ['i5_commit'])) {
            $array_dsn [] = 'CMT=' . $options ['i5_commit'];
        }
        if (isset($options ['i5_date_fmt'])) {
            $array_dsn [] = 'DFT=' . $options ['i5_date_fmt'];
        }
        if (isset($options ['i5_date_sep'])) {
            $array_dsn [] = 'DSP=' . $options ['i5_date_sep'];
        }
        if (isset($options ['i5_decimal_sep'])) {
            $array_dsn [] = 'DEC=' . $options ['i5_decimal_sep'];
        }
        if (isset($options ['i5_time_fmt'])) {
            $array_dsn [] = 'TFT=' . $options ['i5_time_fmt'];
            $array_dsn [] = 'TSP=' . $options ['i5_time_fmt'];
        }

        $dsn = implode(';', $array_dsn);

        return $dsn;
    }

    protected static function generate_odbc_options($options) {
        $array_opt = array();

        if (isset($options ['i5_naming']) && $options ['i5_naming'] == true) {
            $array_opt [SQL_ATTR_DBC_SYS_NAMING] = 1;
            self::$_sql_separator = '/';
        } else {
            $array_opt [SQL_ATTR_DBC_SYS_NAMING] = 0;
        }
        if (isset($options ['i5_libl']) && $options ['i5_libl'] != '') {
            self::$_liblist [] = $options ['i5_libl'];
        }
        if (isset($options ['i5_lib']) && $options ['i5_lib'] != '') {
            self::$_liblist [] = $options ['i5_lib'];
        }
        if (isset($options ['i5_commit'])) {
            $array_opt [SQL_ATTR_COMMIT] = $options ['i5_commit'];
        }
        if (isset($options ['i5_date_fmt'])) {
            $array_opt [SQL_ATTR_DATE_FMT] = $options ['i5_date_fmt'];
        }
        if (isset($options ['i5_date_sep'])) {
            $array_opt [SQL_ATTR_DATE_SEP] = $options ['i5_date_sep'];
        }
        if (isset($options ['i5_decimal_sep'])) {
            $array_opt [SQL_ATTR_DECIMAL_SEP] = $options ['i5_decimal_sep'];
        }
        if (isset($options ['i5_time_fmt'])) {
            $array_opt [SQL_ATTR_TIME_FMT] = $options ['i5_time_fmt'];
        }
        if (isset($options ['i5_time_sep'])) {
            $array_opt [SQL_ATTR_TIME_SEP] = $options ['i5_time_sep'];
        }

        return $array_opt;
    }

}
