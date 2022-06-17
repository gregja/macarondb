<?php

/**
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 *
 * @category   MacaronDB
 * @package    DB
 * @license    New BSD License
 * @version    DB/PDO/DB2IBMi/DBConnex.php 2012-03-28 09:15:47
 */
class PDO_DB2IBMi_DBConnex {

    protected static $_instance;
    protected static $_dsn;
    protected static $_sql_separator = '.';

    private function __construct() {

    }

    public static function getInstance($system, $user, $password, &$options = array(), $persistent = false) {

        $is_IBMi = (php_uname('s') == 'OS400' || PHP_OS == "AIX" || PHP_OS == "OS400") ? true : false;

        if ($is_IBMi) {
            /*
             * l'implémentation PDO_Ibm est incompléte, mais il est néanmoins intéressant de pouvoir la tester en déclarant
             * le DSN de cette façon :
             */
            $dsn = 'ibm:' . $system;
        } else {
            $dsn = 'odbc:DRIVER={IBM i Access ODBC Driver};SYSTEM=' . $system;

            // Attention à ne pas ajouter de ";" inutile à la fin d'un DSN, car PDO n'apprécie pas du tout
            $dsn_temp = self::generate_dsn($options);
            if ($dsn_temp != '') {
                $dsn .= ';' . $dsn_temp;
            }

        }
        /*
         * Permet d'activer le mode Prepare/Execute qui par défaut est émulé par PDO (si le SGBD ne renvoie pas à PDO
         * l'information comme quoi il gére lui méme la préparation des requêtes)
         * Ne sachant pas si le driver "IBM i Access ODBC " renvoie cette information à PDO, la désactivation
         * effectuée ici est une mesure préventive.
         */
        $options_cnx = array(
            /*
             * Permet d'activer le mode Prepare/Execute qui par défaut est émulé par PDO (si le SGBD ne renvoie pas à PDO
             * l'information comme quoi il gére lui méme la préparation des requêtes)
             * Ne sachant pas si le driver "IBM i Access ODBC " renvoie cette information à PDO, la désactivation
             * effectuée ici est une mesure préventive, ou de précaution.
             */
            PDO::ATTR_EMULATE_PREPARES => FALSE,
        );
        if ($persistent === true) {
            $options_cnx [] = PDO::ATTR_PERSISTENT;
        }
        try {
            self::$_instance = new PDO($dsn, $user, $password, $options_cnx);
        } catch (PDOException $e) {
            error_log('FATAL ERROR : PDOException sur connexion DB dans la méthode ' . __METHOD__ . ' de la classe ' . __CLASS__);
            error_log('FATAL ERROR : DSN= ' . $dsn);
            error_log('FATAL ERROR : ' . $e->getMessage());
            return false ;
        } catch (Exception $e) {
            error_log('FATAL ERROR : Exception sur connexion DB dans la méthode ' . __METHOD__ . ' de la classe ' . __CLASS__);
            error_log('FATAL ERROR : DSN= ' . $dsn);
            error_log('FATAL ERROR : ' . $e->getMessage());
            return false ;
        }

        if (self::$_instance instanceof PDO) {
            self::$_instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$_instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			if (isset($options ['DB2_ATTR_CASE'])) {
				$casse = strtoupper($options ['DB2_ATTR_CASE']);
				if ($casse == 'LOWER') {
					self::$_instance->setAttribute ( PDO::ATTR_CASE, PDO::CASE_LOWER );
				} else {
					if ($casse == 'NATURAL') {
						self::$_instance->setAttribute ( PDO::ATTR_CASE, PDO::CASE_NATURAL );
					} else {
                        // $casse == 'UPPER'
						self::$_instance->setAttribute ( PDO::ATTR_CASE, PDO::CASE_UPPER );
					}
				}
			}
        }

        return self::$_instance;
    }

    /*
     * paramètres de connexion DB2 i5 transformés en DSN
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
         * si $options ['i5_libl'] est un tableau, alors on le transforme en chaîne (postes séparés par un blanc pour db2_connect)
         * tant qu'on ne sait pas si $options ['i5_libl'] est un tableau contenant plus d'une bibliothéque, on considère que
         * c'est le mode DB2_I5_NAMING_OFF qui doit être retenu comme option par défaut (syntaxe full SQL)
         */
        if (!isset($options ['i5_naming']) || !is_bool($options ['i5_naming'])) {
            $options ['i5_naming'] = false;
        }

        if (isset($options ['i5_libl']) && is_array($options ['i5_libl']) && count($options ['i5_libl']) > 0) {
            // tableau à transformer en chaîne de caractères
            $options ['i5_libl'] = implode(' ', $options ['i5_libl']);
        }

        /*

          Valeurs définies pour le niveau d'isolation, pour le connecteur DB2_Connect :
          DB2_I5_TXN_NO_COMMIT = 1
          DB2_I5_TXN_READ_UNCOMMITTED = 2
          DB2_I5_TXN_READ_COMMITTED = 3
          DB2_I5_TXN_REPEATABLE_READ = 4
          DB2_I5_TXN_SERIALIZABLE = 5

          Les valeurs peuvent être transmises au connecteur sous forme de chaîne de caractères :
          - No Commit = *NC ou *NONE ou NO_COMMIT
          - Uncommitted Read = *UR ou *CHG ou READ_UNCOMMITTED
          - Repeatable Read = *RR ou *ALL ou REPEATABLE_READ
          - Cursor Stability = *CS ou READ_COMMITTED
          - Read Stability = *RSéou SERIALIZABLE

          Valeurs définies pour le niveau d'isolation, pour PDO (mot clé "CMT" ou "CommitMode" dans le DSN) :
          0 = Commit immediate (*NONE)
          1 = Read committed (*CS)
          2 = Read uncommitted (*CHG)
          3 = Repeatable read (*ALL)
          4 = Serializable (*RR)
         */
        if (!isset($options ['i5_commit'])) {
            $options ['i5_commit'] = 0;
        } else {
            // si valeur déjà de type numérique entier, alors on la considère comme valide et on la prend telle quelle
            if (!is_int($options ['i5_commit'])) {
                $options ['i5_commit'] = strtoupper($options ['i5_commit']);
                switch ($options ['i5_commit']) {
                    // *NC (No Commit)
                    case '*NONE' :
                    case '*NC' :
                    case 'NO_COMMIT' : {
                            $options ['i5_commit'] = 0;
                            break;
                        }
                    // *UR (Uncommitted Read)
                    case '*UR' :
                    case '*CHG' :
                    case 'READ_UNCOMMITTED' : {
                            $options ['i5_commit'] = 2;
                            break;
                        }
                    // *RR (Repeatable Read)
                    case '*RR' :
                    case '*ALL' :
                    case 'REPEATABLE_READ' : {
                            $options ['i5_commit'] = 3;
                            break;
                        }
                    // *CS (Read committed)
                    case '*CS' :
                    case 'READ_COMMITTED' : {
                            $options ['i5_commit'] = 1;
                            break;
                        }
                    // *RR (Serializable)
                    case '*RR' :
                    case 'SERIALIZABLE' : {
                            $options ['i5_commit'] = 4;
                            break;
                        }
                    default : {
                            // Mode NC par défaut
                            $options ['i5_commit'] = 0;
                        }
                }
            }
        }

        if (!isset($options ['i5_date_fmt'])) {
            $options ['i5_date_fmt'] = 5; // format ISO par défaut
        } else {
            // si valeur déjà de type numérique entier, alors on la considère comme valide et on la prend telle quelle
            // sinon on convertit la chaîne dans la valeur numérique correspondante
            if (!is_int($options ['i5_date_fmt'])) {
                $options ['i5_date_fmt'] = strtoupper($options ['i5_date_fmt']);
                switch ($options ['i5_date_fmt']) {
                    case '*ISO' : {
                            $options ['i5_date_fmt'] = 5;
                            break;
                        }
                    case '*EUR' : {
                            $options ['i5_date_fmt'] = 6;
                            break;
                        }
                    case '*DMY' : {
                            $options ['i5_date_fmt'] = 2;
                            break;
                        }
                    case '*YMD' : {
                            $options ['i5_date_fmt'] = 3;
                            break;
                        }
                    case '*MDY' : {
                            $options ['i5_date_fmt'] = 1;
                            break;
                        }
                    case '*USA' : {
                            $options ['i5_date_fmt'] = 4;
                            break;
                        }
                    case '*JIS' : {
                            $options ['i5_date_fmt'] = 7;
                            break;
                        }
                    case '*JUL' : {
                            $options ['i5_date_fmt'] = 0;
                            break;
                        }
                    default : {
                            // Format ISO par défaut
                            $options ['i5_date_fmt'] = 5;
                        }
                }
            }
        }

        if (!isset($options ['i5_date_sep'])) {
            $options ['i5_date_sep'] = 1; //  "dash" (-) par défaut
        } else {
            // si valeur déjà de type numérique entier, alors on la considère comme valide et on la prend telle quelle
            // sinon on convertit la chaîne dans la valeur numérique correspondante
            if (!is_int($options ['i5_date_sep'])) {
                $options ['i5_date_sep'] = strtoupper($options ['i5_date_sep']);
                switch ($options ['i5_date_sep']) {
                    case '*SLASH' : {
                            $options ['i5_date_sep'] = 0;
                            break;
                        }
                    case '*DASH' : {
                            $options ['i5_date_sep'] = 1;
                            break;
                        }
                    case '*PERIOD' : {
                            $options ['i5_date_sep'] = 2;
                            break;
                        }
                    case '*COMMA' : {
                            $options ['i5_date_sep'] = 3;
                            break;
                        }
                    case '*BLANK' : {
                            $options ['i5_date_sep'] = 4;
                            break;
                        }
                    default : {
                            //  "dash" (-) par défaut
                            $options ['i5_date_sep'] = 1;
                        }
                }
            }
        }

        if (!isset($options ['i5_time_fmt'])) {
            $options ['i5_time_fmt'] = 0; // hh:mm:ss (*HMS)
        } else {
            // si valeur déjà de type numérique entier, alors on la considère comme valide et on la prend telle quelle
            // sinon on convertit la chaîne dans la valeur numérique correspondante
            if (!is_int($options ['i5_time_fmt'])) {
                $options ['i5_time_fmt'] = strtoupper($options ['i5_time_fmt']);
                switch ($options ['i5_time_fmt']) {
                    case '*ISO' : {
                            $options ['i5_time_fmt'] = 2;
                            break;
                        }
                    case '*EUR' : {
                            $options ['i5_time_fmt'] = 3;
                            break;
                        }
                    case '*USA' : {
                            $options ['i5_time_fmt'] = 1;
                            break;
                        }
                    case '*JIS' : {
                            $options ['i5_time_fmt'] = 4;
                            break;
                        }
                    case '*HMS' : {
                            $options ['i5_time_fmt'] = 0;
                            break;
                        }
                    default : {
                            // hh:mm:ss (*HMS)
                            $options ['i5_time_fmt'] = 0;
                        }
                }
            }
        }

        if (!isset($options ['i5_time_sep'])) {
            $options ['i5_time_sep'] = 0; //  "colon" (:) par défaut
        } else {
            // si valeur déjà de type numérique entier, alors on la considère comme valide et on la prend telle quelle
            // sinon on convertit la chaîne dans la valeur numérique correspondante
            if (!is_int($options ['i5_time_sep'])) {
                $options ['i5_time_sep'] = strtoupper($options ['i5_time_sep']);
                switch ($options ['i5_time_sep']) {
                    case '*COLON' : {
                            $options ['i5_time_sep'] = 0;
                            break;
                        }
                    case '*PERIOD' : {
                            $options ['i5_time_sep'] = 1;
                            break;
                        }
                    case '*COMMA' : {
                            $options ['i5_time_sep'] = 2;
                            break;
                        }
                    case '*BLANK' : {
                            $options ['i5_time_sep'] = 3;
                            break;
                        }
                    default : {
                            // "colon" (:) par défaut
                            $options ['i5_time_sep'] = 0;
                        }
                }
            }
        }

        if (!isset($options ['i5_decimal_sep'])) {
            $options ['i5_decimal_sep'] = 0; // "period" par défaut
        } else {
            // si valeur déjà de type numérique entier, alors on la considère comme valide et on la prend telle quelle
            // sinon on convertit la chaîne dans la valeur numérique correspondante
            if (!is_int($options ['i5_decimal_sep'])) {
                $options ['i5_decimal_sep'] = strtoupper($options ['i5_decimal_sep']);
                switch ($options ['i5_decimal_sep']) {
                    case '*PERIOD' : {
                            $options ['i5_decimal_sep'] = 0;
                            break;
                        }
                    case '*COMMA' : {
                            $options ['i5_decimal_sep'] = 1;
                            break;
                        }
                    default : {
                            // Séparateur par défaut
                            $options ['i5_decimal_sep'] = 0;
                        }
                }
            }
        }

        // si valeur déjà de type numérique entier, alors on la considère comme valide et on la prend telle quelle
        // sinon on convertit la chaîne dans la valeur numérique correspondante
        if (!is_int($options ['i5_decimal_sep'])) {
            $options ['i5_decimal_sep'] = strtoupper($options ['i5_decimal_sep']);
            switch ($options ['i5_decimal_sep']) {
                case '*PERIOD' : {
                        $options ['i5_decimal_sep'] = 0;
                        break;
                    }
                case '*COMMA' : {
                        $options ['i5_decimal_sep'] = 1;
                        break;
                    }
                default : {
                        // Séparateur par défaut
                        $options ['i5_decimal_sep'] = 0;
                    }
            }
        }

        if (!isset($options ['DB2_ATTR_CASE']) && !isset($options ['db2_attr_case'])) {
            $options ['DB2_ATTR_CASE'] = 'UPPER'; // result set avec noms de colonnes en majuscules par défaut
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
            // les 3 valeurs possibles sont UPPER, LOWER et NATURAL, donc peu importe la maniére dont ces valeurs ont été saisies
            // (exemples : DB2_CASE_UPPER, CASE_UPPER, ou UPPER, en majuscules ou minuscules), on normalise les valeurs é
            // UPPER, LOWER et NATURAL, pour faciliter leur transmission à PDO.
            $search_attr_case = stripos('UPPER', $options ['DB2_ATTR_CASE']);
            if ($search_attr_case !== false) {
                $options ['DB2_ATTR_CASE'] = 'UPPER';
            } else {
                $search_attr_case = stripos('LOWER', $options ['DB2_ATTR_CASE']);
                if ($search_attr_case !== false) {
                    $options ['DB2_ATTR_CASE'] = 'LOWER';
                } else {
                    $search_attr_case = stripos('NATURAL', $options ['DB2_ATTR_CASE']);
                    if ($search_attr_case !== false) {
                        $options ['DB2_ATTR_CASE'] = 'NATURAL';
                    } else {
                        $options ['DB2_ATTR_CASE'] = 'UPPER';
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
        }
        if (isset($options ['i5_time_sep'])) {
            $array_dsn [] = 'TSP=' . $options ['i5_time_sep'];
        }
        if (isset($options ['i5_override_ccsid'])) {
            $options ['CCSID'] = $options ['i5_override_ccsid'] ;
            unset ($options ['i5_override_ccsid']);
        }
        if (isset($options ['CCSID'])) {
            $option_ccsid = strtoupper($options ['CCSID']);
            if ($option_ccsid == 'UTF-8' || $option_ccsid == 'UTF8') {
                $option_ccsid = '1208';
            }
            $array_dsn [] = 'CCSID=' . $option_ccsid;
        }
        $dsn = implode(';', $array_dsn);

        return $dsn;
    }

    /*
     * renvoie le séparateur SQL à utiliser en fonction du type de nommage déclaré
     * ( nommage SQL => "."  ; ou nommage Systéme IBM i => "/" )
     */

    public static function getSqlSeparator() {
        return self::$_sql_separator;
    }

}
