<?php

class BaseTable 
{
    /**
     * mysqli instance
     * 
     * @var Connection 
     */
    private $m_oConn;
    
    public function __construct($conn) 
    {
        $this->m_oConn = $conn;
    }
    
    /*
     * Executes a query. Returns array of associative arrays if any 
     * mysqli_results exist.
     */
    public function execute($strQuery)
    {
        $result = $this->m_oConn->get()->query($strQuery);
        
        //If there's an error in the query then die
        if(!$result){
            throw new Exception("Query: $strQuery, Error: ");
        }
        
        //If there is a mysqli_result then return it
        if($result instanceof mysqli_result){
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        }
    }
    
    /*
     * Returns ID of last insert
     */
    public function selectLastInsertID(){
        $result = $this->execute("SELECT LAST_INSERT_ID() as 'id';");
        return $result[0]['id'];
    }
}
