<?php

/**
 * Class for handling errors. 
 */
class Error 
{
    /**
     * List of errors
     *  
     * @var array
     */
    private $m_aError;
    
    public function __construct()
    {
        $this->m_aError = array();
    }
    
    /**
     * Add a single error
     * 
     * @param string $strError
     */
    public function add($strError)
    {
        array_push($this->m_aError, $strError);
    }
    
    /**
     * Add many errors
     * 
     * @param array $aErrors
     */
    public function addAll($aErrors)
    {
        $this->m_aError = array_merge($this->m_aError, $aErrors);
    }
    
    /**
     * Returns true if errors exist, false otherwise
     * 
     * @return boolean
     */
    public function hasError()
    {
        return !empty($this->m_aError);
    }
    
    /**
     * Get the errors
     * 
     * @return array 
     */
    public function get()
    {
        return $this->m_aError;
    }
}
    