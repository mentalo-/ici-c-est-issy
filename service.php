<?php
    $debug = 0;
    if ( $debug == 1 ) {
        $response = array(
            'status'    => 'OK',
            'message'   => 'coucou',
            'key'       => '1',
            'value'     => 'abc'
        );
        echo json_encode($response); die;
    }
    session_start();
    
    // On force la désactivation du cache de nombreux clients et proxy
    header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
    
    // Encodage
    header('Content-type: text/html; charset=iso-8859-15');
    // header('Content-Type: text/html; charset=utf-8');
    
    // Cross-Origin Resource Sharing (autorisation d'une requête provenant d'un client sur un autre domaine
    // header("Access-Control-Allow-Origin: *"); // nom du domaine d'appel qui est autorisé
    // header("Access-Control-Allow-Methods: POST"); // méthodes autorisées
    // header("Access-Control-Max-Age: 0"); // durée de mise en case de la réponse
    
    $isXmlHttpRequest = array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
    if ( !$isXmlHttpRequest ) {
        die("Page uniquement accessible par un appel AJAX");
    }
    
    require_once('Classes/Api.php');
    $api = new Api();
    
    // Réponse AJAX (init)
    $response = array(
        'status'    => '',
        'message'   => '',
        'key'       => '',
        'value'     => ''
    );
    
    // Données postées
    $service    = ( isset($_POST['service']) )  ? $_POST['service'] : false;
    $values     = ( isset($_POST['values']) )   ? $_POST['values']  : false;
    
    // Vérification des paramètres
    if ( !$service ) {
        $response = array(
            'status'    => 'error',
            'message'   => "Paramètre 'service' invalide",
            'key'       => 'service',
            'value'     => $service
        );
        echo json_encode($response); die;
    }
    if ( !$values || !is_array($values) ) {
        $response = array(
            'status'    => 'error',
            'message'   => "Paramètre 'values' invalide",
            'key'       => 'values',
            'value'     => $values
        );
        echo json_encode($response); die;
    }
    
    // Disponibilité du service
    if ( !$api->isSetService($service) ) {
        $response = array(
            'status'    => 'error',
            'message'   => "Service '$service' indisponible",
            'key'       => 'service',
            'value'     => $service
        );
        echo json_encode($response); die;
    }
    
    // Appel du service
    $result = $api->{$service}($values);
    
    $response = array(
        'status'    => 'success',
        'message'   => "Catégories",
        'key'       => 'result',
        'value'     => $result
    );
    
    echo json_encode($response); die;