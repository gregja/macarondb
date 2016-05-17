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
 * @version    DBActiveRecord.php 2012-03-28 09:15:47
 */
interface intDBActiveRecord {

    /**
     * Constructeur recevant en entr�e l'instance d�finissant la base de donn�es utilis�e 
     * @param DBInstance $base
     */
    function __construct($base);

    /**
     * M�thode servant � alimenter les colonnes de la table SQL sous-jacente
     * @param text $field
     * @param string_or_numeric $value
     */
    function __set($field, $value);

    /**
     * M�thode permettant de r�cup�rer la valeur d'une colonne de table SQL sous-jacente
     * @param text $field
     */
    function __get($field);

    /**
     * M�thode permettant de charger en m�moire une ligne de BD si elle existe
     * @param integer_or_string $id
     */
    function load($id);

    /**
     * M�thode permettant de sauvegarder une ligne en BD.
     * Si la ligne a �t� pr�alablement "charg�e" alors est mise � jour par appel de la m�thode update(),
     * dans le cas contraire, elle est cr��e par appel de la m�thode create()
     */
    function save();

    /**
     * M�thode permettant de mettre � jour une ligne en BD
     * La mise � jour ne peut aboutir que si la ligne a �t� pr�alablement "charg�e" par la m�thode load(),
     * � condition que ce chargement ait abouti (on peut le contr�ler via la m�thode isLoaded() )
     */
    function update();

    /**
     * M�thode permettant de cr�er une ligne en BD
     * La bonne ex�cution de la mise � jour peut �tre v�rifi�e via la m�thode is_updated() 
     */
    function create();

    /**
     * M�thode permettant de supprimer une ligne en BD
     * La suppression ne peut aboutir que si la ligne a �t� pr�alablement "charg�e" par la m�thode load(),
     * � condition que ce chargement ait abouti (on peut le contr�ler via la m�thode isLoaded() )
     * La bonne ex�cution de la cr�ation peut �tre v�rifi�e via la m�thode isCreated()
     */
    function delete();

    /**
     * M�thode permettant de v�rifier la bonne ex�cution de la m�thode load()
     */
    function isLoaded();

    /**
     * M�thode permettant de v�rifier la bonne ex�cution de la m�thode create()
     */
    function isCreated();

    /**
     * M�thode permettant de v�rifier la bonne ex�cution de la m�thode update()
     */
    function isUpdated();

    /**
     * M�thode permettant de v�rifier la bonne ex�cution de la m�thode delete()
     */
    function isDeleted();

    /**
     * M�thode permettant de v�rifier la bonne ex�cution de la m�thode save()
     */
    function isSaved();

    /**
     * M�thode permettant de renvoyer sous la forme d'un tableau l'int�gralit� des colonnes
     * d'une ligne BD, charg�e au pr�alable en m�moire via la m�thode load()
     */
    function getDatas();

    /**
     * M�thode permettant de charger en m�moire l'int�gralit� des colonnes d'une ligne de BD
     * Ces donn�es devront ensuite �tre sauvegard�es par les m�thodes save(), update() ou create()
     * @param array $datas
     */
    function setDatas($datas);

    /**
     * M�thode permettant de renvoyer un tableau contenant la liste des champs du formulaire 
     * de cr�ation/mise � jour.
     * Le tableau renvoy� est format� de mani�re � �tre compatible avec la classe CrudManager
     */
    function getFormElements();

    /**
     * M�thode permettant de v�rifier si une ligne existe en BD sans la charger en m�moire
     * @param integer_or_string $key
     */
    function isKeyUsed($key);

    /**
     * M�thode destin�e � renvoyer une requ�te SQL pr�-format�e pour �tre utilis�e dans le 
     * chargement d'une liste, notamment pour l'affichage d'une liste pagin�e dans un module 
     * de type CRUD (Create - Retrieve - Update - Delete)
     */
    function getCrudSelectDefault();

    /**
     * M�thode destin�e � renvoyer un tableau des lignes de la table, format� de mani�re
     * � �tre facilement int�grable dans un champ de type SELECT d'un formulaire
     */
    function getCrudSelectField();

    /**
     * M�thode permettant de renvoyer le nom de la table SQL sous-jacente
     */
    function getTableName();

    /**
     * M�thode de r�cup�rer le nom de la classe "fille" courante
     */
    function getClassName();

    /**
     * Permettant de r�cup�rer, pour information, le mode de mise � jour de l'identifiant de la table sous-jacente
     * ce mode peut �tre de type : *manual, *auto, *sequence
     * TODO : le mode *sequence n'est pas impl�ment� dans le wrapper pour MySQL, en revanche il fonctionne pour DB2
     */
    function getModeIncrId();

    /**
     * M�thode permettant de renvoyer le dernier ID ins�r� en base de donn�e
     */
    function getLastIdInserted();
}

/**
 * Adaptation du design pattern "Active Record" pour le d�veloppement d'applications "m�tier"
 * Cette m�thode a �t� sp�cialement con�ue pour faciliter la mise au point de 
 * modules de type CRUD (Create - Retrieve - Update - Delete)
 *
 * @category    DB
 * @package     DB_ActiveRecord
 * @author      Gregory Jarrige <gregory_jarrige@yahoo.fr>
 * @version     Release: 1.0.0
 */
abstract class DBActiveRecord implements intDBActiveRecord {

    /**
     * resource BD re�ue en entr�e du constructeur
     * */
    protected $db;

    /**
     * Nom de la table SQL � d�finir dans la classe fille
     * */
    public $table_name = '';
    public $schema_name = '';
    protected $table_sql = '';
    protected $key_name = '';
    protected $key_value = '';
    protected $user_key = '';
    protected $description_field = '';
    protected $fields_name = array();
    protected $fields_value = array();
    protected $fields_update = array();
    protected $flag_is_loaded = false;
    protected $flag_is_created = false;
    protected $flag_is_deleted = false;
    protected $flag_is_updated = false;
    protected $flag_is_saved = false;
    protected $with_no_commit = true;
    protected $form_elements = array();
    protected $dependancies = array();
    protected $autofill_on_update = array();
    protected $autofill_on_insert = array();
    protected $autofill_on_delete = array(); // for "logical delete" only
    protected $physical_delete = true; // if false then "logical delete" (not implemented in that version)
    protected $incr_id_mode = '*manual'; // "*manual", "*auto", "*sequence"
    protected $incr_id_seq = array('sequence_schema' => '', 'sequence_name' => ''); // for "*sequence" only 
    protected $last_id_inserted = null;
    protected $db_version = 6; // version 6 par d�faut (pour b�n�ficier de certaines fonctionnalit�s DB2 int�ressantes

    public function __construct($base) {
        $this->db = $base;
        $this->flag_is_loaded = false;
        if ($this->schema_name == '') {
            $this->table_sql = $this->table_name;
        } else {
            $this->table_sql = trim($this->schema_name) . '{SEPARATOR}' . trim($this->table_name);
        }

        /*
         * certaines fonctionnalit�s de DB2 ne sont disponibles qu'� partir de la V6, comme par exemple NEW TABLE () 
         * si le client est en version inf�rieure, il est n�cessaire de le param�trer
         */
        if (isset($GLOBALS['conf_app']['base_version']['version'])) {
            $this->db_version = intval($GLOBALS['conf_app']['base_version']['version']);
            if ($this->db_version < 5 || $this->db_version > 7) {
                throw new Exception("Version de DB2 incoh�rente.");
            }
        }
    }

    public function __set($field, $value) {
        // $field = mb_strtolower ( $field );
        if (in_array($field, $this->fields_name)) {
            $this->fields_update [$field] = $value;
        } else {
            /*
             * on regarde s'il existe une m�thode "set_" suivie de ce nom, si oui alors on l'ex�cute
             */
            $setter = 'set_' . ucwords($field);
            if (method_exists($this, $setter)) {
                return $this->$setter($value);
            }
            /*
             * on regarde s'il existe une m�thode "get_" suivie de ce nom, 
             * si oui alors rejet car il s'agit d'une utilisation incorrecte de la m�thode indiqu�e
             * si non alors rejet �galement car la propri�t� n'existe pas dans le tableau $this->fields_name
             * et on ne sait pas quoi en faire.
             */
            $getter = 'get_' . ucwords($field);
            if (method_exists($this, $getter)) {
                throw new Exception("La propri�t� '{$field}' est en lecture seule.");
            } else {
                throw new Exception("La propri�t� '{$field}' n'est pas d�finie.");
            }
        }
    }

    public function __get($field) {

        // $field = mb_strtolower ( $field );
        if (in_array($field, $this->fields_name)) {
            return $this->fields_value [$field];
        }
        /*
         * on regarde s'il existe une m�thode "get_" suivie de ce nom, si oui alors on l'ex�cute
         */
        $getter = 'get_' . ucwords($field);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        throw new Exception("La propri�t� '{$field}' n'est pas d�finie.");
    }

    public function load($id) {
        $this->key_value = $id;
        $sql = 'select ' . implode(', ', $this->fields_name) . ' from ' . $this->table_sql . ' where ' . $this->key_name . ' = ? ';
        $data = $this->db->selectOne($sql, array($id));
        if (is_array($data) && count($data) > 0) {
            $this->flag_is_loaded = true;
            $this->flag_is_created = false;
            $this->flag_is_deleted = false;
            $this->flag_is_updated = false;
            $this->flag_is_saved = false;

            foreach ($this->fields_name as $field_name) {
                if (array_key_exists($field_name, $data)) {
                    $this->fields_value [$field_name] = rtrim($data [$field_name]);
                } else {
                    $field_name = strtoupper($field_name);
                    if (array_key_exists($field_name, $data)) {
                        $this->fields_value [$field_name] = rtrim($data [$field_name]);
                    } else {
                        throw new Exception("Nom de champ {$field_name} invalide.");
                    }
                }
            }
        }
    }

    public function save() {
        if ($this->flag_is_loaded) {
            $this->update();
            if ($this->flag_is_updated) {
                $this->flag_is_saved = true;
            }
        } else {
            $this->create();
            if ($this->flag_is_created) {
                $this->flag_is_saved = true;
            }
        }
    }

    public function update() {
        $markers = array();
        $terms = array();
        foreach ($this->fields_update as $name => $value) {
            if ($name != $this->key_name) {
                //$markers [] = $name . '=?';
                //$terms [] = $value;
                list($markers [], $terms []) = $this->initAutoFill($name, $value);
            }
        }

        foreach ($this->autofill_on_update as $name => $value) {
            list($markers [], $terms []) = $this->initAutoFill($name, $value);
        }

        if (count($terms) > 0) {
            $sql = "UPDATE {$this->table_sql} SET ";
            $sql .= implode(', ', $markers);
            $sql .= " WHERE $this->key_name = ?" . $this->addNoCommit();
            $terms = array_merge($terms, array($this->key_value));
            $flag = $this->db->executeCommand($sql, $terms);
            if ($flag == 1) {
                $this->flag_is_updated = true;
            }
        }
    }

    public function create() {
        $fields = array();
        $terms = array();
        $markers = array();
        foreach ($this->fields_update as $key => $value) {
            if ($key != $this->key_name || $this->incr_id_mode == '*manual') {
                $fields [] = $key;
                //$terms [] = $value ;
                list($filler, $terms []) = $this->initAutoFill($key, $value);
                $markers [] = '?';
            }
        }

        foreach ($this->autofill_on_insert as $key => $value) {
            list($filler, $terms []) = $this->initAutoFill($key, $value);
            $fields [] = $key;
            $markers [] = '?';
        }

        if ($this->incr_id_mode == '*sequence') {
            // TODO : impl�menter le m�canisme d'incr�mentation d'une s�quence DB2
        }

        if ($this->db_version < 6) {
            /*
             * en V5, on ne peut utiliser la clause "NEW TABLE", du coup
             * pour r�cup�rer le dernier identifiant cr��, apr�s avoir
             * ex�cut� la requ�te d'insertion, on est oblig�
             * de proc�der de diff�rents fa�ons, selon le mode de gestion de l'identifiant : 
             * - si mode = *AUTO ou *SEQUENCE alors on appelle la m�thode "getLastInsertId()"
             * - si mode = *MANUAL alors on r�cup�re la valeur de l'ID directement dans le 
             *    tableau des valeurs saisies par l'utilisateur
             */
            $sql = 'INSERT INTO ' . $this->table_sql . ' ( ';
            $sql .= implode(',', $fields) . ') VALUES (';
            $sql .= implode(',', $markers) . ')';
            $sql .= $this->addNoCommit();
            $nbins = $this->db->executeCommand($sql, $terms, true);
            if ($nbins == 1) {
                $this->flag_is_created = true;
                if ($this->incr_id_mode == '*manual') {
                    $this->last_id_inserted = $this->fields_update[$this->key_name];
                } else {
                    $this->last_id_inserted = $this->db->getLastInsertId();
                }
            }
        } else {
            /*
             * en V6, DB2 permet d'utiliser la clause "NEW TABLE", et ainsi 
             * de faire une requ�te "tout en un" incluant :
             * - l'insertion dans la base
             * - la r�cup�ration de l'identifiant cr��
             */
            $sql = 'select xcrud.' . $this->key_name . ' from NEW TABLE (';
            $sql .= 'INSERT INTO ' . $this->table_sql . ' ( ';
            $sql .= implode(',', $fields) . ') VALUES (';
            $sql .= implode(',', $markers) . ')';
            $sql .= ') xcrud';
            $sql .= $this->addNoCommit();

            $data = $this->db->selectOne($sql, $terms, true);
            $this->last_id_inserted = $data[0];
            if (!is_null($this->last_id_inserted)) {
                $this->flag_is_created = true;
            }
        }
    }

    public function delete() {
        if ($this->flag_is_loaded) {
            if ($this->physical_delete) {
                $sql = "DELETE FROM {$this->table_sql} WHERE $this->key_name = ?" . $this->addNoCommit();
                $flag = $this->db->executeCommand($sql, array($this->key_value));
                if ($flag == 1) {
                    $this->flag_is_deleted = true;
                }
            } else {
                $markers = array();
                $terms = array();
                foreach ($this->autofill_on_delete as $name => $value) {
                    list($markers [], $terms []) = $this->initAutoFill($name, $value);
                }
                if (count($terms) > 0) {
                    $sql = "UPDATE {$this->table_sql} SET ";
                    $sql .= implode(', ', $markers);
                    $sql .= " WHERE $this->key_name = ?" . $this->addNoCommit();
                    $terms = array_merge($terms, array($this->key_value));
                    $flag = $this->db->executeCommand($sql, $terms);
                    if ($flag == 1) {
                        $this->flag_is_deleted = true;
                    }
                }
            }
        } else {
            return false;
        }
    }

    public function isLoaded() {
        return $this->flag_is_loaded;
    }

    public function isCreated() {
        return $this->flag_is_created;
    }

    public function isUpdated() {
        return $this->flag_is_updated;
    }

    public function isDeleted() {
        return $this->flag_is_deleted;
    }

    public function isSaved() {
        return $this->flag_is_saved;
    }

    public function getDatas() {
        $datas = array();
        foreach ($this->fields_name as $key) {
            if (isset($this->fields_value[$key])) {
                $datas [$key] = $this->fields_value[$key];
            } else {
                $datas [$key] = null;
            }
        }
        return $datas;
    }

    public function setDatas($datas) {
        if (!is_array($datas) || count($datas) <= 0) {
            return;
        }
        foreach ($this->fields_name as $key) {
            if (isset($datas[$key])) {
                $this->fields_update[$key] = $datas [$key];
            }
        }
    }

    public function isKeyUsed($key) {
        return $this->db->valueIsExisting($this->table_sql, $this->key_name, $key);
    }

    public function getFormElements() {
        $elements = array();
        foreach ($this->form_elements as $key => $value) {
            $elements [$key] = $value;
        }
        return $elements;
    }

    public function getCrudSelectDefault() {
        $sql = 'SELECT BASE.' . $this->key_name . ' as CRUD_ID, BASE.' . $this->user_key . ' AS CODE, BASE.' . $this->description_field . ' AS LIBELLE FROM ' . $this->table_sql . ' BASE ';
        $pos = strpos($sql, '{SEPARATOR}');
        if ($pos !== false) {
            $sql = str_replace('{SEPARATOR}', $this->db->getSqlSeparator(), $sql);
        }

        return $sql;
    }

    public function getCrudSelectField() {
        $sql = 'SELECT BASE.' . $this->key_name . ' as CODE, BASE.' . $this->user_key . ' concat \' - \' concat BASE.' . $this->description_field . ' AS LIBELLE FROM ' . $this->table_sql . ' BASE ';
        $sql .= ' ORDER BY BASE.' . $this->key_name;
        return $this->db->selectKeyValuePairs($sql);
    }

    public function getModeIncrId() {
        return $this->incr_id_mode;
    }

    public function getTableName() {
        return $this->table_sql;
    }

    public function getClassName() {
        return __CLASS__;
    }

    public function getLastIdInserted() {
        return $this->last_id_inserted;
    }

    protected function initAutoFill($name, $value) {
        $marker = '';
        $term = '';

        if (is_array($value)) {
            if (isset($value['entity']) && isset($value['id']) && isset($value['get_col'])) {
                $class = trim($value['entity']);
                $col_id = trim($value['id']);
                $col_value_id = isset($this->fields_update[$col_id]) && !empty($this->fields_update[$col_id]) ? $this->fields_update[$col_id] : $this->key_value[$col_id];
                $get_col = trim($value['get_col']);
                require_once $class . '.php';
                $element_aux = new $class($this->db);
                $element_aux->load($col_value_id);
                if ($element_aux->isLoaded()) {
                    $marker = $name . '=?';
                    $term = $element_aux->$get_col;
                } else {
                    error_log('autofill : class => ' . $class . ' load =>' . $col_value_id);
                    throw new Exception('Lecture d\'une entit� auxiliaire incorrecte dans ' . __METHOD__ . ' de ' . __CLASS__ . ' (' . $col_value_id . ')');
                }
                unset($element_aux);
            } else {
                throw new Exception('Classe "entity" (ou propri�t� "id") incorrectement d�finie pour m�thode ' . __METHOD__ . ' de ' . __CLASS__);
            }
        } else {
            switch ($value) {
                case '*date': {
                        $marker = $name . '=?';
                        $term = date('Y-m-d');
                        break;
                    }
                case '*datenum': {
                        $marker = $name . '=?';
                        $term = intval(date('Ymd'));
                        break;
                    }
                case '*time': {
                        $marker = $name . '=?';
                        $term = date('H:i:s');
                        break;
                    }
                case '*timenum': {
                        $marker = $name . '=?';
                        $term = intval(date('His'));
                        break;
                    }
                case '*datetime': {
                        $marker = $name . '=?';
                        $term = date('Y-m-d H:i:s');
                        break;
                    }
                case '*timestamp': {
                        $marker = $name . '=?';
                        list($usec, $sec) = explode(" ", microtime());
                        $microsec = intval($usec * 1000000);
                        $term = date('Y-m-d H:i:s') . '.' . strval($microsec);
                        break;
                    }
                case '*user': {
                        $marker = $name . '=?';
                        $term = isset($_SESSION['phl_USER']) ? $_SESSION['phl_USER'] : '*unknown';
                        break;
                    }
                default: {
                        $marker = $name . '=?';
                        $term = $value;
                    }
            }
        }
        return (array($marker, $term));
    }

    protected function addNoCommit() {
        if ($this->with_no_commit == true) {
            return ' WITH NC ';
        } else {
            return '';
        }
    }

    public function __clone() {
        throw new Exception('Fonction __clone() indisponible.');
    }

    public function __destruct() {
        
    }

    public function __wakeup() {
        /*
         *  m�thode permettant d'interdire la d�s�rialisation de cette classe
         */
        throw new Exception('Fonction __wakeup() indisponible.');
    }

}

