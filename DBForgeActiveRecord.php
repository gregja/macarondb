<?php

/**
 * 
 * @author gjarrige
 *
 */

interface intDBForgeActiveRecord {
	public static function generator($datastruct) ;
	public static function getListColumns ($datastruct) ;
}

abstract class DBForgeActiveRecord implements intDBForgeActiveRecord {
	
	public static function generator($datastruct, $table_name='YOUR_TABLE', $table_schema='YOUR_BASE') {
		$phpcode = self::genHeader($datastruct, $table_name, $table_schema) ;
		$phpcode .= self::genElements ( $datastruct );
		$phpcode .= self::genFooter($datastruct) ;
		return $phpcode ;
	}
	
	protected static function genFooter($datastruct) {
		$ar_footer = <<<BLOC_CODE
		parent::__construct ( \$base );
		}
		
	}
BLOC_CODE;
		return $ar_footer;
	}
	
	protected static function genHeader($datastruct, $table_name='YOUR_TABLE', $table_schema='YOUR_BASE') {
		$table_name = trim($table_name) ;
		$table_schema = trim($table_schema) ;
		
		$ar_columns = self::getListColumns($datastruct) ;
		$ar_header = <<<BLOC_CODE
		require_once (dirname ( __FILE__ ) . '/../macaronDB/DBActiveRecord.php');
		
class YourtableModel extends DBActiveRecord implements intDBActiveRecord {
	
	public function __construct(\$base) {
		\$this->table_name = '{$table_name}'; // � modifier apr�s g�n�ration
		\$this->schema_name = '{$table_schema}'; // � modifier apr�s g�n�ration
		
		// liste des colonnes de la table
		\$this->fields_name = array ({$ar_columns});
				
		\$this->key_name = 'id'; // nom de la colonne "id"
		\$this->key_value = null; // valeur par d�faut de la colonne "id" (avant utilisation de la m�thode load() )
		
		// nom de la colonne "cl�" d'un point de vue utilisateur (peut �tre la colonne "id" ou un "identifiant manuel")
		\$this->user_key = \$this->key_name ;   // � modifier apr�s g�n�ration seulement si n�cessaire
		
		// colonne "id" en incr�mentation automatique si fix�e � *auto
		\$this->incr_id_mode = '*auto' ;
				
		// nom de la colonne contenant la description principale (utile pour l'affichage de liste dans un module de type "CRUD")
		\$this->description_field = 'nom';   // � modifier apr�s g�n�ration si n�cessaire
		
		// liste des champs mis � jour automatiquement lors d'un Update SQL ( � modifier apr�s g�n�ration si n�cessaire )
        \$this->autofill_on_update = array(
        			'upd_date'=>'*date', 
        			'upd_time'=>'*time',
        			'upd_usid'=>'*user'
        ) ;
        
        // liste des champs mis � jour automatiquement lors d'un INSERT SQL ( � modifier apr�s g�n�ration si n�cessaire )
        \$this->autofill_on_insert = array(
        			'cre_date'=>'*date', 
        			'cre_time'=>'*time',
        			'cre_usid'=>'*user'
        ) ;
        		
BLOC_CODE;
		return $ar_header;
	}	
	
	public static function getListColumns ($datastruct) {
		return implode(', ', self::getColumns ($datastruct)) ;
	}
	
	protected static function getColumns ($datastruct) {
		$colons = array() ;
		foreach ($datastruct as $key=>$value) {
			$colons [] = "'".strtolower($key)."'" ;
		}
		return $colons ;
	}
	
	protected static function genElements($datastruct) {
		$php_gen = array ();
		$php_gen [] = '$this->form_elements = array();' . PHP_EOL;
		foreach ( $datastruct as $struckey => $strucvalue ) {
			$php_gen [] = '$this->form_elements [\'' . $struckey . '\'] = array(';
			$php_gen [] = self::generateArray ( $strucvalue );
			$php_gen [] = ');' . PHP_EOL;
		}
		return implode ( "\n", $php_gen );
	}
	
	protected static function generateArray($datastruct) {
		
		$elements = array ();
		foreach ( $datastruct as $struckey => $strucvalue ) {
			if (! is_array ( $strucvalue )) {
				if ($struckey == 'label' && is_string($strucvalue)) {
					$strucvalue = htmlentities($strucvalue, ENT_QUOTES) ;
				}
				if (is_int ( $strucvalue ) || is_float ( $strucvalue )) {
					$elements [] = "'$struckey' => $strucvalue";
				} else {
					$strucvalue = trim ( $strucvalue );
					if ($strucvalue == '1') {
						$elements [] = "'$struckey' => true";
					} else {
						$elements [] = "'$struckey' => '$strucvalue'";
					}
				}
			} else {
				$elements [] = "'$struckey' => array(" . PHP_EOL . self::generateArray ( $strucvalue ) . PHP_EOL . ')';
			}
		}
		return implode ( ", \n", $elements );
	}

}
	