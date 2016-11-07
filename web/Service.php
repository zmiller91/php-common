<?php

/**
 * Base class for a Web Service. 
 *
 * @author zmiller
 */
abstract class Service {
    
    const GET = "GET";
    const POST = "POST";
    
    /**
     * @var Connection 
     */
    protected $m_oConnection;
    
    /**
     * @var Error 
     */
    protected $m_oError;
    
    /**
     * @var User
     */
    protected $m_oUser;
    
    /**
     * @var mixed 
     */
    protected $m_mData;
    
    /**
     * @var array 
     */
    protected $m_aInput;
    
    /**
     * @var string 
     */
    protected $m_strMethod;
    
    private $m_aHeaders;
    private $m_iStatusCode;
    
    public function __construct($strMethod, $aInput)
    {
        $this->m_strMethod = $strMethod;
        $this->m_aInput = $aInput;
        $this->m_oError = new Error();
        $this->m_mData = null;
        
        $this->m_iStatusCode = 200;
        $this->m_aHeaders = array();
        $this->m_oConnection = new Connection(DB_HOST, DB_USER, 
                DB_PASSWD, DB_NAME);
        
        $this->m_oUser = new User($this->m_oConnection);
    }
    
    /**
     * Executes the lifecycle of a service and returns success or failure
     * 
     * @return boolean success
     */
    public function run()
    {
        $bSuccess = $this->authorize() && 
                $this->validate();
        
        switch($this->m_strMethod)
        {
            case self::GET:
                $bSuccess = $bSuccess && $this->get();
                break;
            
            case self::POST:
                $bSuccess = $bSuccess && $this->post();
                break;
            
            default:
                $bSuccess = false;
                $this->methodNotAllowed();
                break;
        }
        
        $this->marshal();
        return $bSuccess;
    }
    
    /**
     * Add a header that will be set upon marshalling
     * 
     * @param string $key
     * @param string $value
     */
    protected function setHeader($key, $value)
    {
        $this->m_aHeaders[$key] = $value;
    }
    
    /**
     * Convenience method for setting a status code
     * 
     * @param int $iStatusCode
     */
    protected function setStatusCode($iStatusCode)
    {
        if(isset($iStatusCode) && is_int($iStatusCode))
        {
            $this->m_iStatusCode = $iStatusCode;
        }
    }
    
    /**
     * Shutdown the service and echo the result
     */
    private function marshal()
    {   
        if($this->m_oError->hasError())
        {
            $this->m_oConnection->rollback();
            $this->m_mData = array("errors" => $this->m_oError->get());
            if($this->m_iStatusCode < 400)
            {
                $this->setStatusCode(500);
            }
        }
        else 
        {
            $this->m_oConnection->commit();
        }
        
        $this->m_oConnection->close();
        http_response_code($this->m_iStatusCode);
        foreach($this->m_aHeaders as $key => $value)
        {
            header($key . ": " . $value);
        }
        
        if(isset($this->m_mData))
        {
            echo json_encode($this->m_mData);
        }
    }
    
    /**
     * Method will get called on a GET request. Overriding methods should not
     * call the super method. If a child class does not support a POST request
     * then the parent class will return false and a 405 status code will be 
     * set.
     * 
     * @return boolean success or failure
     */
    protected function get()
    {
        $this->methodNotAllowed();
        return false;
    }
    
    /**
     * Method will get called on a POST request. Overriding methods should not
     * call the super method. If a child class does not support a POST request
     * then the parent class will return false and a 405 status code will be 
     * set.
     * 
     * @return boolean success or failure
     */
    protected function post()
    {
        $this->methodNotAllowed();
        return false;
    }
    
    /**
     * Convenience method for setting 405 Method Not Allowed
     */
    private function methodNotAllowed()
    {
        $this->setStatusCode(405);
        $this->setHeader("Allow", implode($this->allowableMethods(), ", "));
    }
    
    /**
     * Returns true if the request is authorized to use the service 
     * 
     * @return boolean
     */
    abstract protected function authorize();
    
    /**
     * Returns true if the requests input is valid
     * 
     * @return boolean
     */
    abstract protected function validate();
    
    /**
     * Returns an array representing the allowed HTTP methods (e.g. GET, POST)
     * 
     * @return array
     */
    abstract protected function allowableMethods();
}
