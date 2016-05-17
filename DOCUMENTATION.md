Point à noter : ce projet a été longtemps hébergé sur Sourceforge, j'ai décidé de le rapatrier sur Github tout récemment. 
Ne vous étonnez donc pas de ne pas trouver d'historique de téléchargement, ni d'historique de mise à jour. 

#Historique et Présentation
Le projet MacaronDB ne s'est pas fait en un jour, mais est le fruit d'une lente maturation. 
La première version de ce projet - qui ne s'appelait pas encore "MacaronDB" - se présentait sous la forme d'une classe PHP abstraite, que j'avais appelée PDOWrapper (car elle s'appuyait exclusivement sur PDO). Elle regroupait quelques unes des méthodes essentielles du projet, telles que "selectOne()", "selectBlock()", etc... 
Pour développer cette première version, je m'étais inspiré d'un "wrapper" proposé par Jack D. Herrington, dans son livre "PHP Hacks" (éditions O'Reilly). Le "wrapper" proposé par Jack était écrit pour la librarie PearDB, mais son adaptation à PDO se révéla aisée. 
En travaillant sur différents projets utilisant la classe PDOWrapper, je l'ai progressivement enrichie, en lui ajoutant de nouvelles méthodes, au fur et à mesure de mes besoins (executeCommand(), export2XML(), etc...). 

La classe PDOWrapper contenait des méthodes statiques qui s’utilisait de la façon suivante :

$data = PDOWrapper::selectBlock($connecteur, $requete_sql, $parametres) ;

Il est encore possible d’utiliser certaines classes du projet MacaronDB de cette manière, mais l’on verra qu’il est possible d’utiliser une autre manière, qui me semble aujourd’hui plus pratique, au moins pour traiter les opérations courantes.

Lorsque j'ai commencé à utiliser PHP sur des bases de données DB2 (et en particulier DB2 pour IBM i), je me suis rendu compte que disposer d'une librarie équivalente à celle que j'avais écrite pour mes projets personnels, était indispensable. 
Mais je ne voulais pas utiliser mon propre code, car je pensais - peut être un peu naïvement - que je trouverais un projet open source adapté à mes besoins. J'ai vite déchanté, et ce pour plusieurs raisons :
- si plusieurs librairies orientées « bases de données » proposent des connecteurs pour un certain nombre de bases de données, la base de données DB2 est très souvent oubliée, 
- les rares librairies proposant un support de DB2 proposent un support incomplet, dédié à DB2 pour Windows/Linux, et ne prenant pas en compte les spécificités de DB2 pour IBM i,
- à l'époque où j'effectuais ces recherches, certaines librairies étaient encore écrites selon une approche objet correspondant au PHP4, je les ai éliminées d’office de ma recherche. 

Déçu par le résultat de mes recherches, et après réflexion, j’ai décidé d’adapter ma classe PDOWrapper à DB2. 
Tout allait bien tant que j'utilisais MacaronDB sur un Zend Server pour Windows, en attaquant la base de données DB2 pour IBM i via PDO et le "iSeries Access ODBC Driver". 
Par contre cela s'est corsé quand j'ai voulu porter MacaronDB sur un Zend Server pour IBM i. Car j'ai eu la désagréable surprise de constater que l'implémentation de PDO pour DB2 sur serveur IBM i était incomplète, et donc inutilisable. 

Pour synthétiser un peu la situation, voici les manières les plus courantes d'utiliser PHP en environnement IBMi :
- à partir d’un Zend Server pour Windows ou Linux, on peut accéder à une base DB2 pour IBMi avec PDO et le « ISERIES ACCESS ODBC DRIVER », driver fourni par IBM.
- à partir d’un Zend Server pour IBMi, on peut accéder à une base de données DB2 pour IBMi via l’extension « ibm_db2 » (extension fournie en standard dans ce contexte).

On peut également ajouter que :
- l’accès à une base de données « DB2 Express C » installée sur un serveur Windows ou Linux peut se faire à partir d’un Zend Server pour Windows ou Linux, via l'extension ibm_db2 ou via PDO.
- l’accès à une base de données MySQL5 sur IBMi se fait de la même manière qu'en environnement Windows ou Linux (via PDO ou mysqli). 

Il faut souligner qu'en ce début d'année 2016, les choses tendent à s'améliorer et qu'IBM fait des efforts pour améliorer le connecteur PDO implémenté sur IBMi (pour de plus amples infos : youngiprofessionals.com). 
Mais à l'époque où j'ai écrit MacaronDB, PDO sur IBMi était vraiment inutilisable, ce qui m'avait conduit à développer un wrapper complet pour ce contexte d'utilisation.

J'avais donc intégré dans l'architecture de MacaronDB la possibilité de travailler soit avec PDO, soit avec des extensions spécifiques telles que ibm_db2.
Par ailleurs, il m'était apparu nécessaire de ne plus travailler exclusivement avec une classe abstraite et un jeu de méthodes statiques. 
Travailler avec une classe instanciable, plutôt qu'avec une classe abstraite, offre quelques avantages indéniables qu'il serait trop long de développer ici. Mais cela m’a conduit à ajouter une nouvelle classe à MacaronDB, classe que j'ai appelée "DBInstance". 
Grâce à cette nouvelle classe, l’exemple de tout à l’heure peut s’écrire de la façon suivante :

$data = $connecteur->selectBlock( $requete_sql, $parametres ) ;

C’est cette méthode que je décrirai en détails dans les exemples suivants.

Définition d’une connexion
On place généralement la déclaration du (ou des) connecteur(s) bases de données dans un script particulier qui sera appelé au démarrage de l’application. On parle souvent de « script d’amorçage » ou de « script de configuration » pour désigner ce type de script. Le script de configuration sera appelé par les autres scripts de l’application via la fonction PHP require_once(), pour éviter de rappeler plusieurs fois ce script par erreur.
On commence par regarder si la variable $cnx_db01 n’a pas été déjà déclarée. Ce test n’est pas absolument indispensable, surtout si vous avez pris soin de bien charger ce script via la fonction require_once(). On initialise ensuite un tableau $options contenant quelques directives propres à la base DB2 pour IBM i, tel que le mode de « nommage » utilisé par les requêtes (qui peut être « système » ou « SQL »), la liste des bibliothèques où se situent les objets DB2 utilisés par l’application, ainsi que le mode de retour des noms de colonnes renvoyés par SQL dans les jeux de données (result sets). Dans l’exemple, les noms des colonnes seront renvoyés systématiquement en majuscule.
Le test surligné en jaune permet d’identifier « à la volée » si l’application s’exécute sur un serveur IBMi ou pas, on adapte donc le choix du connecteur au contexte d’exécution.
Je vous propose de lire le code, puis les explications complémentaires qui suivent :

if (! isset ( $cnx_db01 )) {
	/*
	 * Tableau des options de configuration de la base DB2
	 */	
	$options = array ();
	$options['i5_naming'] = true ;
	$options['i5_libl'] = array(‘mabib1’, ‘mabib2’, ‘mabib3’) ;
	$options['DB2_ATTR_CASE'] = 'UPPER'  ;	
	
	/*
	 * Si la plateforme est de type IBMi alors on se connecte à la base de données avec DB2_connect,
	 * sinon on se connecte à la base de données avec PDO
	 */
	if (php_uname('s') == 'OS400' || PHP_OS == "AIX" || PHP_OS == "OS400") {		
		/*
		 * Ouverture d'une connexion BD sur un serveur IBM i, avec 
              * DB2 Connect
		 */
		 require_once 'DB2/IBMi/DBWrapper.php';
		 require_once 'DB2/IBMi/DBConnex.php';
		 require_once 'DB2/IBMi/DBInstance.php';
		 $cnx_db01 = new DB2_IBMi_DBInstance('*LOCAL', '', '', $options ) ;
	} else {
		/*
		 * Connexion BD sur un serveur Windows ou Linux avec PDO
		 */
		 require_once 'PDO/DB2IBMi/DBWrapper.php';
		 require_once 'PDO/DB2IBMi/DBConnex.php';
		 require_once 'PDO/DB2IBMi/DBInstance.php';
		/*
		 * définition de l’adresse IP, du profil et du mot de passe 
              * de connexion au serveur IBM i
		 */
     $ipa = ‘adresse IP de votre serveur IBM I’ ;
     $usr = ‘votre profil’ ;
     $pwd = ‘mot de passe correspondant au profil’ ;
		 $cnx_db01 = new PDO_DB2IBMi_DBInstance($ipa, $usr, $pwd, $options ) ;
	}
}

Dans l’exemple qui précède, j’ai choisi de montrer une méthode permettant de rendre votre code PHP indépendant de la plateforme d’exécution. En effet, si vous souhaitez que votre application puisse s’exécuter tantôt sur un Zend Server pour Windows, tantôt sur un Zend Server pour IBM i, et ce en modifiant le moins de code possible, alors vous pouvez le faire via le code de la page précédente.
En revanche, si votre application est destinée à ne « tourner » que sur un serveur IBMi, alors vous pouvez ne conserver qu’une partie du code précédent, soit :	
	$options = array ();
	$options['i5_naming'] = true ;
	$options['i5_libl'] = array(‘mabib1’, ‘mabib2’, ‘mabib3’) ;
	$options['DB2_ATTR_CASE'] = 'UPPER'  ;	
  require_once 'DB2/IBMi/DBWrapper.php';
	require_once 'DB2/IBMi/DBConnex.php';
	require_once 'DB2/IBMi/DBInstance.php';
	$cnx_db01 = new DB2_IBMi_DBInstance('*LOCAL', '', '', $options ) ;

A partir du moment où votre connexion est établie, vous pouvez bénéficier du mécanisme d’auto-complétion de votre IDE préféré, ce qui rend l’utilisation de MacaronDB plus facile.

Les différentes méthodes à votre disposition sont les suivantes : 

- function selectOne($sql, $args = array(), $fetch_mode_num = false);
- function selectBlock($sql, $args = array());
- function selectKeyValuePairs($sql, $args = array());
- function executeCommand($sql, $args = array(), $count_nb_rows = true);
- function executeSysCommand ($cmd) ;
- function callProcedure($proc_name, $proc_schema, &$args = array(), $return_resultset = false);
- function getStatement($sql, $args = array());
- function getFetchAssoc($st);
- function getPagination($sql, $args, $offset, $nbl_by_page, $order_by = '');
- function getScrollCursor($sql, $args, $offset, $nbl_by_page, $order_by = '' ) ;
- function export2CSV($sql, $args = array());
- function export2XML($sql, $args = array(), $tag_line = '', $gen_header=true) ;
- function export2insertSQL($sql, $args = array());
- function getLastInsertId($sequence = '');
- function valueIsExisting($table, $nomcol, $valcol, $where_optionnel = '');
- function valueIsExistingOnOtherRecord($table, $nomcol, $valcol, $idencours, $where_optionnel = '');
- function getInfoDatabase();
- function countNbRowsFromTable($table, $schema = '');
- function countNbRowsFromSQL($sql, $args = array());

Pour exécuter une requête SQL ne renvoyant qu'une seule ligne, vous pouvez écrire ceci : 

$sql = 'select code, description from mytable where code1 = ? and code2 = ?';
$params = array ($value1, $value2 ) ;
$data = $cnx_db01->selectOne ( $sql, $params);

Dans l'exemple ci-dessus, si la requête $sql a abouti, alors la variable $data contient un tableau associatif à une dimension contenant les données retournées par SQL. En revanche, si la requête n'a pas abouti, la variable $data contiendra le booléen "false". 
Pour information, les 2 paramètres de la méthode selectOne() sont la requête SQL à exécuter, et un tableau PHP optionnel contenant les valeurs qui vont se substituer aux points d'interrogation dans la requête SQL. La méthode selectOne() exécute la requête en s'appuyant sur les méthode prepare() et execute() du connecteur base de données sous-jacent. 
Il est important de noter que toutes les requêtes exécutées par les méthodes de MacaronDB sont exécutées selon ce principe, qui garantit la meilleure sécurité possible contre les attaques dites par "injection SQL". Je vous déconseille formellement de créer vos requêtes SQL en concaténant les éléments variables de vos clauses WHERE avec le code de la requête. Ce faisant, vous rendriez vos requêtes vulnérables aux attaques dites "par injection SQL". 
Si vous souhaitez récupérer un tableau avec des postes numérotés (plutôt qu'un tableau associatif), alors il vous suffit d'ajouter le booléen "true" en troisième paramètre (optionnel) de la méthode selectOne(). Exemple : 

$data = $cnx_db01->selectOne ( $sql, array ($value1, $value2 ), true );

Si votre requête est susceptible de renvoyer de 1 à X lignes, alors vous devez utiliser la méthode « selectBlock » (plutôt que « selectOne »). Exemple : 

$sql = 'select code, description from mytable where code1 = ? and code2 = ?';
$data = $cnx_db01->selectBlock ( $sql, array ($value1, $value2 ) ); 

Dans l'exemple ci-dessus, la variable $data contiendra – si tout s’est bien passé - un tableau associatif à 2 dimensions (que vous pourrez « parcourir » via un foreach par exemple). Si en revanche la requête a échoué, alors $data contiendra « false ».

Si vous souhaitez récupérer un jeu de données sous la forme d'un tableau à deux dimensions, facile à utiliser pour la génération de champs de formulaire de type "select" (liste déroulante), alors vous apprécierez sûrement la méthode selectKeyValuePairs() qui s'utilise de la façon suivante : 

$sql = 'select code, description from mytable';
$data = $cnx_db01->selectKeyValuePairs( $sql ); 

Si vous souhaitez exécuter une requête de type INSERT, UPDATE ou DELETE, alors vous devez recourir aux services de la méthode executeCommand(), qui fonctionne sur le même principe que les méthodes vues précédemment. Exemple : 

$sql = 'update mytable set col1 = ? where id = ?';
$data = $cnx_db01->executeCommand( $sql, array($value, $id) ); 

Si vous souhaitez exécuter une commande système IBM i, par exemple pour déclencher un DSPPGMREF destiné à alimenter une table DB2, vous devez utiliser la méthode executeSysCommand(). Exemple : 

$cmd = 'DSPPGMREF …';
$data = $cnx_db01->executeSysCommand( $cmd ); 

Les méthodes “export2CSV()”, “export2XML()” et “export2insertSQL()” permettent de générer très simplement des données aux formats indiqués dans le nom des méthodes. Ces méthodes sont déjà opérationnelles, mais elles sont adaptées au traitement de volumes de données limités. Pour traiter de gros volumes de données, on pourra s’inspirer du code utilisé dans ces méthodes, mais il sera certainement souhaitable d’écrire des scripts PHP dédiés écrivant directement sur disque pour éviter de surcharger la mémoire du serveur d’exécution. La méthode “export2insertSQL()” peut présenter quelques faiblesses si elle est exécutée via le connecteur PDO car dans ce cas les données exportées dans le script SQL sont toutes encadrées par des apostrophes, même si elles sont numériques. Cette faiblesse sera corrigée dans une prochaine version du projet MacaronDB.

#Monitoring des erreurs

Si une requête n’aboutit pas suite à une erreur, la log des erreurs de PHP est automatiquement alimentée avec différentes informations qui sont précieuses pour le débogage. Ces informations sont les suivantes  :
- le code et le message d’erreur renvoyés par le connecteur base de données
- la requête SQL en erreur
- le tableau des arguments transmis à la requête

#Analyse des performances

Il est possible de mesurer le temps d’exécution d’une requête SQL, en activant le profiler via la méthode setProfilerOn().
La désactivation se fait au moyen de la méthode setProfilerOff().
Les informations relatives aux performances sont enregistrées dans la log PHP.
