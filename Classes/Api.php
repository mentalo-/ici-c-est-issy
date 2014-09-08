<?php

require_once('DAO.php');

class Api
{

    // const C_CONNECTION_POOL = 1;
    public $dao;
    
    public function __construct() {
        $this->dao = DAO::getInstance(); // connexion à la db
    }
    
    public function getClass() {
        echo __CLASS__;
    }
    
    /*
     *  Vérifie que le service existe et est utilisable
     *  @param  $name   string  Nom du service à vérifier
     *  @return         bool    true si service utilisable, false sinon
     */
    public function isSetService($name) {
        return ( method_exists($this, $name) );
    } // end isSetService()
    
    /**
     *  Liste les catégories
     *  @param  $ids    array   Id des catégories souhaitées
     */
    public function getCategories($ids = array())
    {
        if ( !is_array($ids) )  return false;
        
        try {
            // SELECT id, nom FROM categories WHERE niveau in(3, 4)
            $sql     = " SELECT cat1.id, cat1.nom ";
            // , cat1.niveau , cat2.id, cat2.nom as cat_parent ";
            $sql    .= " FROM categories cat1 ";
            $sql    .= " LEFT JOIN categories as cat2 ON cat1.parent_id = cat2.id ";
            $sql    .= " WHERE ( cat1.id != 0 OR cat1.niveau != 0 ) ";
            $sql    .= " AND cat1.actif = 1 ";
            if ( !empty($ids) ) {
                $sql .= " AND cat1.niveau IN ( ";
                $sql .= implode(", ", $ids);
                $sql .= " ) ";
            }
            $sql    .= " ORDER BY cat1.nom ASC ";
            // echo $sql; die;
            $result = $this->dao->loadData($sql);
            // echo "<pre>"; print_r($result); echo "</pre>"; die;
            
            $categories = array();
            if ( !empty($result) ) {
                foreach ( $result as $r ) {
                    $categories[] = array(
                        'id'    => $r['id'], 
                        'nom'   => utf8_encode($r['nom'])
                    );
                }
            }
            // echo "<pre>"; print_r($categories); echo "</pre>"; die;
            return $categories;
            
        } catch ( Exception $e) {
            throw "Erreur : " . $e->getMessage();
        }
    } // end getCategories()
    
    /**
     *  Liste les points d'intérêt
     *  @param  $ids    array   Id des catégories souhaitées
     */
    public function getPois($ids = array())
    {
        if ( empty($ids) || !is_array($ids) ) return false;
        
        $ids_imploded = implode(", ", $ids);
        try {
            $sql    = " SELECT id, titre, description ";
            $sql    .= " FROM poi ";
            $sql    .= " WHERE actif = 1 ";
            $sql    .= " AND ( ";
            $sql    .= "        categorie1 IN ( ". $ids_imploded ." ) ";
            $sql    .= "  OR    categorie2 IN ( ". $ids_imploded ." ) ";
            $sql    .= "  OR    categorie3 IN ( ". $ids_imploded ." ) ";
            $sql    .= "  OR    categorie4 IN ( ". $ids_imploded ." ) ";
            $sql    .= " ) ";
            $sql    .= " ORDER BY titre ASC ";
            // echo $sql; die;
            $result = $this->dao->loadData($sql);
            // echo "<pre>"; print_r($result); echo "</pre>"; die;
            
            $poi = array();
            if ( !empty($result) ) {
                foreach ( $result as $r ) {
                    $p = array(
                        'id'            => $r['id'],
                        'titre'         => utf8_encode($r['titre']),
                        'description'   => utf8_encode($r['description'])
                    );
                    $poi[] = $p;
                }
            }
            // echo "<pre>"; print_r($poi); echo "</pre>"; die;
            return $poi;
            
        } catch ( Exception $e) {
            throw "Erreur : " . $e->getMessage();
        }
    } // end getPois()
    
    /**
     *  Retourne les détails d'un point d'intérêt
     *  @param  $ids    array   Id des POI
     */
    public function getPoiDetail($ids = array())
    {
        if ( empty($ids) || !is_array($ids) ) return false;
        
        $ids_imploded = implode(", ", $ids);
        try {
            $sql    = " SELECT id, titre, description, adresse, code_postal, ville, telephone, url, email, latitude, longitude ";
            $sql    .= " FROM poi ";
            $sql    .= " WHERE actif = 1 ";
            $sql    .= " AND id IN ( ". $ids_imploded ." ) ";
            // echo $sql; die;
            $result = $this->dao->loadData($sql);
            // echo "<pre>"; print_r($result); echo "</pre>"; die;
            
            $poi = array();
            if ( !empty($result[0]) ) {
                $r = $result[0];
                $poi = array(
                    'id'            => $r['id'],
                    'titre'         => utf8_encode($r['titre']),
                    'description'   => utf8_encode($r['description']),
                    'adresse'       => utf8_encode($r['adresse']),
                    'code_postal'   => utf8_encode($r['code_postal']),
                    'ville'         => utf8_encode($r['ville']),
                    'telephone'     => utf8_encode($r['telephone']),
                    'url'           => utf8_encode($r['url']),
                    'email'         => utf8_encode($r['email']),
                    'latitude'      => utf8_encode($r['latitude']),
                    'longitude'     => utf8_encode($r['longitude'])
                );
            }
            // echo "<pre>"; print_r($poi); echo "</pre>"; die;
            return $poi;
            
        } catch ( Exception $e) {
            throw "Erreur : " . $e->getMessage();
        }
    } // end getPoiDetail()
    
    /**
     *  Retourne la liste des X prochains événements
     *  @param  $value      array   Tableau d'une entrée : le nombre d'événements souhaités (20 par défaut)
     */
    public function getAgendaList($value = array(20))
    {
        if ( !is_array($value) || !isset($value[0]) || empty($value[0]) || count($value) != 1 || !is_numeric($value[0]) ) return false;
        
        $nb = $value[0];
        try {
            $content = file_get_contents("http://issy.com/ws/agenda/next/" . $nb);
            
            // le retour doit commencer par commencer par un '[' et terminer par un ']'
            if ( empty($content) || $content[0] != '[' || $content[strlen($content)-1] != ']' ) {
                return false;
            }
            return json_decode($content); // sera réencodé en json dans service.php
        } catch ( Exception $e) {
            throw "Erreur : " . $e->getMessage();
        }
    } // end getAgendaList()
    
    
} // end class
