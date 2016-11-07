<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of User
 *
 * @author zmiller
 */
class User
{
    public static $COOKIE_IDENTIFIER = "wada";
    
    public $m_bLoggedIn;
    public $m_iUserId;
    public $m_strName;
    public $m_aCookie;
    public $m_oError;
    
    private $m_oConnection;
    
    public function __construct($oConnection) 
    {
        $this->m_oConnection = $oConnection;
        $this->m_aCookie = $this->parseCookie();
        $this->m_oError = new Error;
        $this->m_bLoggedIn = false;
    }
    
    public function get()
    {
        $oUser = [];
        $oUser['id'] = $this->m_iUserId;
        $oUser['loggedIn'] = $this->m_bLoggedIn;
        $oUser['errors'] = $this->m_oError->get();
        $oUser['name'] = $this->m_strName;
        return $oUser;
    }
    
    public function authorize()
    {
        $iUser = $this->m_aCookie["user_id"];
        $strSelector = $this->m_aCookie["selector"];
        $strToken = $this->m_aCookie["token"];
        
        // Get the user's session
        $oUserTable = new UserTable($this->m_oConnection);
        $oUserSession = $oUserTable->getUserSession($iUser, $strSelector);
        
        // If there's an error, remove the session and return
        if($oUserTable->m_oError->hasError())
        {
            $this->m_oError->addAll($oUserTable->m_oError->get());
            $this->removeSession();
            return false;
        }
        
        // User session must exist
        if($oUserSession){
            
            // Session expired, remove it
            if(date("Y-m-d H:i:s") > $oUserSession['expiration']){
                $this->removeSession();
                return false;
            }
            
            // Authenticated, update token timestamp
            if($oUserSession['token'] === $strToken){
                
                $this->m_iUserId = $iUser;
                $this->m_strName = $oUserSession['username'];
                $this->m_bLoggedIn = $this->updateSession(
                        $oUserSession['persist'] == 1, $strToken);
                
                return $this->m_bLoggedIn;
            }
                
            // Security violation. User and selector exist but the token does
            // not match. Delete everything.
            else
            {
                $oUserTable->deleteAllSessions($iUser);
                if($oUserTable->m_oError->hasError())
                {
                    $this->m_oError->addAll($this->m_oError->get());
                }
                
                $this->bLoggedIn = false;
                $this->removeSession();
                return false;
            }
        }
        
        //no sessions found
        $this->bLoggedIn = false;
        $this->deleteCookie();
        return false;
    }
    
    public function createSession($iUser, $strName, $bPersist)
    {
        $this->m_bLoggedIn = false;
        $oUserTable = new UserTable($this->m_oConnection);
        $strToken = $this->generateToken();
        $strExpiration = $this->generateExiprationDate(true);
        $strSelector = $oUserTable->createUserSession(
                $iUser, 
                $strToken, 
                $strExpiration, 
                $bPersist);

        if($oUserTable->m_oError->hasError())
        {
            $this->m_oError->addAll($oUserTable->m_oError->get());
            $this->deleteCookie();
            return false;
        }
        
        $this->setCookie($iUser, $strSelector, $strToken);
        $this->m_bLoggedIn = true;
        $this->m_iUserId = $iUser;
        $this->m_strName = $strName;
        return true;
    }
    
    public function removeSession()
    {
        $bSuccess = true;
        if(empty($this->m_aCookie["user_id"]) || empty($this->m_aCookie["token"]))
        {
            $bSuccess = false;
        }
        else 
        {
            $oUserTable = new UserTable($this->m_oConnection);
            $oUserTable->deleteUserSession(
                    $this->m_aCookie["user_id"], 
                    $this->m_aCookie["token"]);

            if($oUserTable->m_oError->hasError())
            {
                $this->m_oError->addAll($oUserTable->m_oError->get());
                $bSuccess = false;
            }
        }
        
        $this->deleteCookie();
        $this->m_bLoggedIn = false;
        $this->m_iUserId = null;
        $this->m_strName = null;
        
        return $bSuccess;
    }
    
    protected function updateSession($bPersist, $strToken)
    {
        $this->m_bLoggedIn = false;
        if(empty($this->m_aCookie["user_id"]) && $this->m_aCookie["selector"])
        {
            return $this->m_bLoggedIn;
        }
        
        $oUserTable = new UserTable($this->m_oConnection);
        $strExpiration = $this->generateExiprationDate($bPersist);
        $oUserTable->updateUserSession(
                $this->m_aCookie["user_id"], 
                $this->m_aCookie["selector"], 
                $strExpiration);

        if($oUserTable->m_oError->hasError())
        {
            $this->m_oError->addAll($oUserTable->m_oError->get());
            return $this->m_bLoggedIn;
        }
        
        $this->setCookie($this->m_aCookie["user_id"], 
                $this->m_aCookie["selector"], $strToken);
        
        $this->m_bLoggedIn = true;
        return $this->m_bLoggedIn;
    }
    
    protected function generateToken(){
        return bin2hex(openssl_random_pseudo_bytes(60));
    }
    
    
    protected function setCookie($strUser, $strSelector, $strToken){
        $strCookie = "$strUser:$strSelector:$strToken";
        setcookie(User::$COOKIE_IDENTIFIER, $strCookie, 0, "/");
    }
    
    public function deleteCookie(){
        setcookie(User::$COOKIE_IDENTIFIER, '', time() - 3600);
        $this->m_aCookie = array();
    }
    
    protected function parseCookie()
    {
        $iUser = null;
        $strSelector = null;
        $strToken = null;
        if(isset($_COOKIE[User::$COOKIE_IDENTIFIER]))
        {
            list($iUser, $strSelector, $strToken) = 
                    explode(':', $_COOKIE[User::$COOKIE_IDENTIFIER], 3);
        }
        
        return array(
            "user_id" => $iUser,
            "selector" => $strSelector, 
            "token" => $strToken
        );
    }
    
    //return an token expiration date in MYSQL DATETIME format
    protected function generateExiprationDate($bPersist){
         
        //2 weeks
        if($bPersist){
            return date("Y-m-d H:i:s", strtotime("+14 days"));
            
        //1 hour
        }else{
            return date("Y-m-d H:i:s", strtotime("+1 hour"));
        }
    }
}
