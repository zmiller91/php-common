<?php

class BaseTable 
{
    /**
     * mysqli instance
     * 
     * @var Connection 
     */
    private $m_oConn;
    
    /**
     *
     * @var ZError 
     */
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
            if(DEBUG) {
                $this->m_oError->add(mysqli_error($this->m_oConn->get()));
                $this->m_oError->add($strQuery);
            }
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
    
    /**
     * Turns a result set into a map with the given keys
     * 
     * @param type $result
     * @param type $keys
     * @return type
     */
    protected function map($result, $keys) {
        $retval = array();
        foreach($result as $r) {
            $retval[$this->createKey($keys, $r)] = $r;
        }
        
        return $retval;
    }
    
    /**
     * Escapes input for SQL injection
     * 
     * @param type $string
     * @return type
     */
    protected function escape($string) {
        return $this->m_oConn->get()->real_escape_string($string);
    }
    
    /**
     * Creates an AND SQL filter. Nulls will not be added and WHERE will 
     * not be prefixed. The keys of $filter are column names and the values of
     * $filter are the column's criteria.
     * 
     * @param array $filter
     * @return string
     */
    protected function createAndEqualsFilter($filter) {
        $sqlFilter = array();
        foreach($filter as $column => $value) {
            if(isset($value)) {
                array_push($sqlFilter, "$column = '$value'");
            }
        }
      
        return implode(" AND ", $sqlFilter);
    }
    
    private function createKey($keys, $object) {
        $parts = array();
        foreach($keys as $k) {
            array_push($parts, $object[$k]);
        }
        
        return implode("-", $parts);
    }
}
