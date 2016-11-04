<?php

/**
 * Wrapper method for managing a transactional mysqli instance.
 */
class Connection 
{
    /**
     * mysqli instance
     * 
     * @var mysqli
     */
    private $m_oConn;
    
    /**
     * Construct a Connection object
     * 
     * @param string $host database url
     * @param string $user database user
     * @param string $password database password
     * @param string $database database name
     */
    public function __construct($host, $user, $password, $database) {
        $this->m_oConn = new mysqli($host, $user, $password);
        $this->m_oConn->select_db($database);
        if ($this->m_oConn->connect_error){
            die("Connection failed because: " . $this->m_oConn->connect_error);
        }
        
        $this->m_oConn->query("START TRANSACTION");
    }
    
    /**
     * Get the mysqli instance
     * 
     * @return mysqli
     */
    public  function get(){
        return $this->m_oConn;
    }
    
    /**
     * Close the mysqli instance
     */
    public function close(){
        $this->m_oConn->close();
    }
    
    /*
     * Commit the mysqli transaction
     */
    public function commit(){
        $this->m_oConn->query("COMMIT");
    }
    
    /**
     * Rollback the mysqli transaction
     */
    public function rollback()
    {
        $this->m_oConn->query("ROLLBACK TRANSACTION");
    }
}
