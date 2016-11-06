<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UserAuth
 *
 * @author zmiller
 */
class Login extends Service
{
    const ERR_INVALID_USERNAME = 'invalid_username';
    const ERR_INVALID_PASSWORD = 'invalid_password';
    
    public function __construct($strMethod, $aInput) 
    {
        parent::__construct($strMethod, $aInput);
    }
    
    protected function allowableMethods() 
    {
        return array(self::POST);
    }

    protected function authorize() 
    {
        return true;
    }

    protected function validate() 
    {
        $bSuccess = true;
        if(empty($this->m_aInput["user"]["name"]))
        {
            $this->setStatusCode(400);
            $this->m_oError->add("A username must be provided");
            $bSuccess = false;
        }
        
        if(empty($this->m_aInput["user"]["pass"]))
        {
            $this->setStatusCode(400);
            $this->m_oError->add("A password must be provided");
            $bSuccess = false;
        }
        
        return $bSuccess;
    }
    
    
    protected function post()
    {
        $oUserTable = new UserTable($this->m_oConnection);
        $oUserCreds = $oUserTable->getUser($this->m_aInput["user"]["name"]);
        $strEncodedPass = base64_encode(
                hash('sha256', $this->m_aInput["user"]["pass"], true));
        
        // Return an error if the user doesnt exist
        if(!$oUserCreds){
            $this->m_oError->add(self::ERR_INVALID_USERNAME);
            return true;
        }
        
        // Return an error if the user has a wrong password
        $bVerified = password_verify($strEncodedPass, $oUserCreds['password']);
        if(!$bVerified)
        {
            $this->m_oError->add(self::ERR_INVALID_PASSWORD);
            return true;
        }
        
        $this->m_oUser->createSession($oUserCreds['id'], 
                $oUserCreds['username'], true);

        if($this->m_oUser->m_oError->hasError())
        {
            $this->setStatusCode(500);
            $this->m_oError->addAll($this->m_oUser->m_oError->get());
            return false;
        }

        $this->m_mData = $this->m_oUser->get();
        return true;
    }
}
