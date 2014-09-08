<?php

require_once(realpath(dirname(__FILE__)) . '/DAO_MYSQL.php');
class DAO extends DAO_MYSQL
{

	const C_DATABASE_SERVEUR    = 'localhost';
	const C_DATABASE_BASE       = 'base';
	const C_DATABASE_PORT       = '1234';        
	const C_DATABASE_UID        = 'uid';
	const C_DATABASE_LOGIN      = 'login';
	const C_DATABASE_PWD        = 'pwd';
    
    // http://blogs.msdn.com/b/brian_swan/archive/2010/11/17/sql-server-driver-for-php-connection-options-connection-pooling.aspx
    const C_CONNECTION_POOL     = 1;
    
	protected static $_instance     = NULL; // instance unique de la bdd
	protected static $_connection   = NULL;
    
    public function getClass() {
        echo __CLASS__;
    }
    
    public function getDbServeur() {
        return self::C_DATABASE_SERVEUR;
    }
    
} // end class
