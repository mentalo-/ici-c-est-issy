<?php
    
    session_start();
    
    // On force la désactivation du cache de nombreux clients et proxy
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    
    // Encodage
    header('Content-type: text/html; charset=iso-8859-15');
    // header('Content-Type: text/html; charset=utf-8');
    
    // Cross-Origin Resource Sharing (autorisation d'une requête provenant d'un client sur un autre domaine
    header("Access-Control-Allow-Origin: test.com"); // nom du domaine d'appel
    header("Access-Control-Allow-Methods: POST"); // méthodes autorisées
    header("Access-Control-Max-Age: 0"); // durée de mise en case de la réponse
    
    $isXmlHttpRequest = array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
    if ( !$isXmlHttpRequest ) {
        die("Page uniquement accessible par un appel AJAX");
    }
    
    require_once('Classes/DAO.php');
    $dao = DAO::getInstance();
    
    // Réponse AJAX (init)
    $response = array(
        'status'    => '',
        'message'   => '',
        'key'       => '',
        'value'     => ''
    );
    
    // Données postées
    // $aIds   = ( isset($_POST['ids']) )  ? $_POST['ids'] :   FALSE; // tableau des id à supprimer
    
    // Sélection
    try {
        // select id, nom FROM categories WHERE niveau in(3, 4)
        $sql     = " SELECT      cat1.id, cat1.nom, cat1.niveau , cat2.id, cat2.nom as cat_parent ";
        $sql    .= " FROM        categories cat1 ";
        $sql    .= " LEFT JOIN   categories as cat2 ON cat1.parent_id = cat2.id ";
        $sql    .= " WHERE       ( cat1.id != 0 OR cat1.niveau != 0 OR cat1.actif != 1 ) ";
        $sql    .= " ORDER BY    cat1.nom ASC ";
        // echo $sql;
        $result = $dao->loadData($sql);
        // echo "<pre>"; print_r($result); echo "</pre>"; die;
        
        foreach( $result as $row ) {
            // echo "<pre>"; print_r($row); echo "</pre>";
        }
        $dbh = null; // fermeture de la connexion
    } catch ( Exception $e) {
        print "Erreur : " . $e->getMessage() . "<br/>";
        die();
    }
    
    // echo "<pre>"; print_r($response); echo "</pre>"; die;
    echo json_encode($response); die;