<?php

abstract class DAO_MYSQL 
{
	const C_DATABASE_SERVEUR    = 'undefined';
	const C_DATABASE_BASE       = 'undefined';
	const C_DATABASE_PORT       = 'undefined';
	const C_DATABASE_UID        = 'undefined';
	const C_DATABASE_LOGIN      = 'undefined';
	const C_DATABASE_PWD        = 'undefined';

	protected static $_instance     = NULL; // instance unique de la bdd
	protected static $_connection   = NULL;
	protected static $_db           = NULL;
	
	public static $_num_rows;
	public static $_field_info;

    
	private function __construct() {
		$this->connect();
	}
	
    
	// On s'assure qu'il y a une seule instance de cette classe (singleton)
	public static function getInstance() {
		if (!self::$_instance) {
			$classe = get_called_class(); // la classe qui appelle getInstance()
			self::$_instance = new $classe();
		}
		return self::$_instance;
	}
	
	
	// On interdit le clonage
	public function __clone() {
		return self::getInstance();
	}
	
	
	// Retourne la connexion
	public static function getConnection() {
		return self::$_connection;
	}
	
	
	// Connexion + choix de la bdd
	public static function connect()
	{
        
        $classe = get_called_class();
        // $servername = $classe::C_DATABASE_SERVEUR; // .",".C_DATABASE_PORT;
        //self::$_connection = mysql_connect($servername, $classe::C_DATABASE_LOGIN, $classe::C_DATABASE_PWD) or die ("Erreur connexion serveur: PLANETE-AGENCE");
        //self::$_db = mysql_select_db($classe::C_DATABASE_BASE, self::$_connection) or die ("Erreur connexion BDD: PLANETE-AGENCE");
        
        try {
            self::$_connection = new PDO(
                  'mysql:host='.$classe::C_DATABASE_SERVEUR.';dbname='.$classe::C_DATABASE_BASE
                , $classe::C_DATABASE_LOGIN
                , $classe::C_DATABASE_PWD
                , array(PDO::ATTR_PERSISTENT => true)
            );
        } catch (PDOException $e) {
            print "Erreur PDO : " . $e->getMessage() . "<br/>";
            die();
        }
        
	} // end connect()
	
	
	// Déconnexion de la bdd
	public static function disconnect() {
		//mysql_close(self::$_connection);
		self::$_connection = false;
	} // end disconnect()
	
	
	/*
	 *  Charge dans un tableau le resultat d'une requête
	 *  @param  $sql            Requête SQL à exécuter
	 *  @param  $multiple_rows  Retourne le resultset si true, sinon retourne une seule ligne
     *  @param  $result_type    Type (string) de tableau attendu, parmi ces valeurs : NUM, NUMERIC, ASSOC, BOTH (par défaut)
	 *  @return $aResult        Résultat de la requête
	 */
    public static function loadData( $sql, $multiple_rows = 1, $result_type = 'ASSOC' )
	{
		// echo $sql.'<br>'; die;
        if ( empty($sql) ) throw new Exception('Empty request');
        
        /*
         * TODO revoir l'usage de cet argument
         * 
        $result_type = strtoupper(trim($result_type));
        switch ($result_type) {
            case 'BOTH' :
                $fetch_type = 3; break;
            case 'NUM' :
            case 'NUMERIC' :
                $fetch_type = 1; break;
            case 'ASSOC' :
            default :
                $fetch_type = 2; break;
        }
        */
        
        try {
            $sth = self::$_connection->prepare($sql);
            $sth->execute();
            $db_result = $sth->fetchAll(PDO::FETCH_ASSOC);
            
            $result = array();
            foreach($db_result as $row) {
                if ( !$multiple_rows )  return $row; // on renvoie uniquement la 1e ligne
                $result[] = $row;
            }
        } catch (PDOException $e) {
            print "Erreur ! " . $e->getMessage() . "<br/>";
            die();
        }
        
        return $result;
        
	} // end loadData()
	
	
	/*
	 *  Exécute une requête
	 *  @param  $sql        Requête SQL à exécuter
	 *  @return  $q         Ressource de requête ou false
	 *                      Les requêtes SQL ne doivent pas se terminer par un point-virgule (";")
							Les requêtes PL/SQL doivent se terminer par un point-virgule (";")
	 */         
	public static function query($sql) {
		// echo $sql.'<br>'; //die;
		if ( is_resource(self::getConnection()) ) {
            $q = mysql_query(self::getConnection(), $sql);
            if ( !$q ) {
                $e =  mysql_errors();
                throw new Exception($e[0]['message'] . ' / ' . $sql );
            }
            return $q;
        }
	} // end query()
    
    
    /** Supprime les données de la table
     *  @param  $table_name     Nom de la table à vider
     *  @return void
     */
    public function truncateTable($table_name) {
        if ( $this->isTableExist($table_name) ) {
            self::query("TRUNCATE TABLE $table_name");
        }
    }
	
	
	/**
	 *  Retourne le nombre de lignes d'un jeu de résultats
	 *  @param  $sSql       Requête SQL à exécuter
	 */
	public function getNumRows($sSql) {
		$q = self::query($sql);
		self::$_num_rows = mysql_num_rows($q);
		return self::$_num_rows;
	} // end getNumRows()
	
	
	/**
	 *  Retourne le type d'un champ
	 *  @param  $i  L'indice du champ souhaité (commence à 0)
					Les champs doit être lus dans l'ordre, i.e. si on accède au champ d'indice 1, 
					alors le champ d'indice 0 ne sera plus disponible
	 */
	public function getFieldInfo($i = 0) {
		$q = self::query($sql);
		// Les indices des champs commencent à 0
		self::$_field_info = mysql_get_field($q, $i);
		return self::$_field_info;
	} // end getFieldInfo()
	
	/*
	 *  Récupère la liste des foreign key pointant sur la table donnée en paramètrant
	 *  @param  $table_name    Nom de la table
	 *  @return $data          Lignes des FK 
	 */
	public function ListForeignKeyToRefTable($table_name)
    {
		$class = get_called_class();
		
		$sql  = " SELECT DISTINCT ";
        $sql .= "  i.TABLE_NAME as TABLE_NAME ";
		$sql .= " , k.COLUMN_NAME as COLUMN_NAME ";
		$sql .= " , k.REFERENCED_TABLE_NAME as REFERENCED_TABLE_NAME ";
		$sql .= " , k.REFERENCED_COLUMN_NAME as REFERENCED_COLUMN_NAME ";
		$sql .= " , i.CONSTRAINT_NAME as CONSTRAINT_NAME ";
		$sql .= " FROM information_schema.TABLE_CONSTRAINTS i ";
		$sql .= " LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME ";
		$sql .= " WHERE i.CONSTRAINT_TYPE = 'FOREIGN KEY' ";
		$sql .= " AND i.TABLE_SCHEMA = '".$class::C_DATABASE_BASE."' ";
		$sql .= " AND k.REFERENCED_TABLE_NAME = '".$table_name ."' ";
		// ou
		// $sql  = " SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME, CONSTRAINT_NAME ";
		// $sql .= " FROM KEY_COLUMN_USAGE ";
		// $sql .= " WHERE TABLE_SCHEMA='".C_DATABASE_BASE."' AND REFERENCED_TABLE_NAME = '".$table_name ."' ";
		//echo($sql);
		return self::loadData($sql);
	}

    /**
     *  Recherche une table dans la base de données
     *  @param  $p_table    Nom de la table recherchée
     *  @return true si la table existe en base, sinon false
     */
    public function isTableExist($p_table)
    {
        if ( empty($table_name) ) return false;
		$class = get_called_class();
		
        if ( empty($p_table) ) return false;

        $sql = "SELECT COUNT(*) as count  FROM INFORMATION_SCHEMA.TABLES WHERE table_schema ='".$class::C_DATABASE_BASE."' and table_name='$p_table';" ;
		$data = self::loadData($sql, 0);
		$nb = intval($data['count']);
        if ( $nb > 0 )  return true; // table trouvée
        else  			return false;
	} // end isTableExist()
    
    
    //Renvoi true si la colonne d'une table existe en base
    /**
     *  Recherche une colonne dans une table de la base de données
     *  @param  $p_table    Nom de la table
     *  @param  $p_colname  Nom de la colonne recherchée
     *  @return true si la colonne existe en base, sinon false
     */
    public function isColumnExist($p_table, $p_colname)
    {
 		$class = get_called_class();
        if ( empty($p_table) || empty($p_colname) ) return false;
		
        $sql  = "SELECT COUNT(*) as count ";
        $sql .= " FROM INFORMATION_SCHEMA.COLUMNS ";
        $sql .= " WHERE table_schema ='".$class::C_DATABASE_BASE."' ";
        $sql .= " AND TABLE_NAME = '$p_table' ";
        $sql .= " AND COLUMN_NAME = '$p_colname'; ";
        $data = self::loadData($sql, 0);
        $nb = intval($data['count']);
        if ( $nb > 0 )  return true; // colonne trouvée
        else            return false;
	} // end isColumnExist()
    
    
    /**
     *  Retourne l'id du dernier enregistrement inséré dans une table
     *  @param  $field_name     Nom du champ (id)
     *  @param  $table_name     Nom de la table
     *  @return $id             Id de l'enregistrement
     */
    public function getLastInsertedId($field_name, $table_name)
    {
        $sql = " SELECT MAX($field_name) AS LastId FROM $table_name ";
        $data = self::loadData($sql, 0);
        $id = intval($data['LastId']);
        return $id;
    } // end getLastInsertedId()
    
     // TODO à migrer en syntaxe MYSQL
    /**
     *  Test de la connexion à une base de données (notamment utilisé dans autocheck.php)
     @params    Paramètres du serveur vérifié
     */
    public function checkBase($serveur, $port, $login, $pwd, $database)
    {
        $msgerr = '';
        
        // Test du SQL - serveur accessible
        $classe = get_called_class();
        
        $fp = fsockopen($classe::C_DATABASE_SERVEUR, $classe::C_DATABASE_PORT);
        if(!$fp)
        {
            $msgerr	= sprintf('Impossible de se connecter au serveur: %s !', $serveur);
            flush();
            ob_flush();
            return $msgerr;
        } 
        else
        {
            // servername
            // Le serveur MS SQL. Il peut également contenir le numéro du port, . 
            // e.g. hostname:port (Linux), ou hostname,port (Windows).
            $classe = get_called_class();
            
            // Test de connexion au serveur et à la base
            try {
            
                $serverName     = $classe::C_DATABASE_SERVEUR.','.$classe::C_DATABASE_PORT;
                $connectionInfo = array( "Database" => $classe::C_DATABASE_BASE, "UID" => $classe::C_DATABASE_UID, "PWD" => $classe::C_DATABASE_PWD);
                $link = sqlsrv_connect( $serverName, $connectionInfo);
            } catch (Exception $e) {
                die('Erreur connexion serveur: '. $servername .' : ' . print_r( sqlsrv_errors(), true));
            }
            
            if ($link == false)	{
                $msgerr	 = sprintf('Impossible de se connecter au serveur: %s !', $servername);
            }
            else {
                sqlsrv_close($link);
            }
        }
        
        return $msgerr;
    } // end checkBase()
    
    
} // end class
