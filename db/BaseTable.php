<?php

class BaseTable 
{
    /**
     * mysqli instance
     * 
     * @var Connection 
     */
    private $m_oConn;
    public $m_oError;
    
    public function __construct($conn) 
    {
        $this->m_oConn = $conn;
        $this->m_oError = new ZError();
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
            $this->m_oError->add("Database Error.");
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
    
    protected function map($result, $keys) {
        $retval = array();
        foreach($result as $r) {
            $retval[$this->createKey($keys, $r)] = $r;
        }
        
        return $retval;
    }
    
    protected function escape($string) {
        return $this->m_oConn->get()->real_escape_string($string);
    }
    
    private function createKey($keys, $object) {
        $parts = array();
        foreach($keys as $k) {
            array_push($parts, $object[$k]);
        }
        
        return implode("-", $parts);
    }
}
