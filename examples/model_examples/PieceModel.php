<?php
//require_once ('....../macaronDB/DBActiveRecord.php');

/*
 * Code source SQL pour cr�ation de la table PIECES sur DB2
CREATE TABLE YOUR_BASE.PIECES ( 
	ID INTEGER
		GENERATED ALWAYS AS IDENTITY
		(START WITH 1, INCREMENT BY 1), 
	NOM   CHAR(30) NOT NULL DEFAULT '', 
	PRIX  DECIMAL(11, 5) NOT NULL DEFAULT 0, 
	CRE_DATE DATE NOT NULL DEFAULT CURRENT_DATE , 
	CRE_TIME TIME NOT NULL DEFAULT CURRENT_TIME , 
	CRE_USID CHAR(20) NOT NULL DEFAULT USER , 
	UPD_DATE DATE NOT NULL DEFAULT CURRENT_DATE , 
	UPD_TIME TIME NOT NULL DEFAULT CURRENT_TIME , 
	UPD_USID CHAR(20) NOT NULL DEFAULT USER ,
	STATUT CHAR (1 ) NOT NULL WITH DEFAULT ' '
)  ; 

CREATE INDEX YOUR_BASE.PIECES01 ON YOUR_BASE.PIECES (ID) ;
CREATE INDEX YOUR_BASE.PIECES02 ON YOUR_BASE.PIECES (NOM, ID) ;

COMMENT ON TABLE YOUR_BASE.PIECES IS 'Liste des Pi�ces' ;

*/

class PieceModel extends DBActiveRecord implements intDBActiveRecord {
	
	public function __construct($base) {
		$this->table_name = 'PIECES';  // nom de la table SQL
		$this->schema_name = 'YOUR_BASE'; // nom du sch�ma SQL
		
		// liste des colonnes de la table
		$this->fields_name = array ('id', 'nom', 'prix', 'cre_date', 'cre_time', 
				'cre_usid', 'upd_date', 'upd_time', 'upd_usid', 'statut' );
				
		$this->key_name = 'id'; // nom de la colonne "id"
		$this->key_value = null; // valeur par d�faut de la colonne "id" (avant utilisation de la m�thode load() )
		
		// nom de la colonne "cl�" d'un point de vue utilisateur (peut �tre la colonne "id" ou un "identifiant manuel")
		$this->user_key = $this->key_name ;		
		
		// nom de la colonne contenant la description principale (utile pour l'affichage de liste dans un module de type "CRUD")
		$this->description_field = 'nom';
		
		// liste des champs mis � jour automatiquement lors d'un Update SQL
        $this->autofill_on_update = array(
        			'upd_date'=>'*date', 
        			'upd_time'=>'*time',
        			'upd_usid'=>'*user'
        ) ;
        
        // liste des champs mis � jour automatiquement lors d'un INSERT SQL
        $this->autofill_on_insert = array(
        			'cre_date'=>'*date', 
        			'cre_time'=>'*time',
        			'cre_usid'=>'*user'
        ) ;

        /*
         * Définition des champs d'un formulaire de mise à jour pour un module de type CRUD
         * Le contenu de $this->form_elements a été conçu pour permettre la génération 
         * automatique de formulaire dans un module de type CRUD, en s'appuyant sur la 
         * classe CrudManager2 (conçue pour générer un formulaire en s'appuyant sur le 
         * projet PEAR::HtmlQuickform 2). La classe CrudManager2 n'est pour l'instant
         * pas fournie avec MacaronDB, car elle nécessite des connaissances avancées dans
         * la mise en oeuvre de PEAR::HtmlQuickform 2, ce qui implique la rédaction d'une
         * documentation spécifique (en cours de préparation). La classe CrudManager2 
         * sera intégrée dans MacaronDB dans un très proche avenir.
         * 		
        $this->form_elements = array();
        $this->form_elements ['nom']= array(
        		'key' => true ,
        		'label' => 'Description',
        		'type' => 'text',
        		'attributes'=> array(
        				'size'=> '30',
        				'maxlength' => '30'
        		),
        		'filters'=>array('*ucase'),
        		'rules' => array(
        				'required' => true,
        				'rangelength' => array(1, 30)
        		)
        );
        $this->form_elements ['prix']= array(
        		'label' => 'Prix',
        		'type' => 'text',
        		'attributes'=> array(
        				'size'=> '10',
        				'maxlength' => '10'
        		),
        		'filters'=> array('*abs'),
        		'rules' => array(
        				'required' => true,
        				'numeric' => true,
        				'nonzero' => true
        		)
        );
        */
           
		parent::__construct ( $base );
	}
	
}


	
