<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Register
 *
 * @author zmiller
 */
class Register extends Service
{
    const ERR_USERNAME_EXISTS = 'username_exists';
    
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
            $this->m_oError->add("Username missing.");
            $bSuccess = false;
        }
        
        if(empty($this->m_aInput["user"]["pass"]))
        {
            $this->m_oError->add("Password missing.");
            $bSuccess = false;
        }
        
        if(empty($this->m_aInput["user"]["verified_pass"]))
        {
            $this->m_oError->add("Verification password missing.");
            $bSuccess = false;
        }
        
        if($bSuccess && 
                $this->m_aInput["user"]["pass"] !== 
                $this->m_aInput["user"]["verified_pass"])
        {
            $this->m_oError->add("Verification password doesn't match.");
            $bSuccess = false;
        }
        
        return $bSuccess;
    }
    
    protected function post()
    {
        // Check if the user exists
        $oUserTable = new UserTable($this->m_oConnection);
        $mResult = $oUserTable->getUser($this->m_aInput["user"]["name"]);
        if($oUserTable->m_oError->hasError())
        {
            $this->m_oError->addAll($oUserTable->m_oError->get());
            return false;
        }
        
        // If a user exists, return an error
        if(!empty($mResult))
        {
            $this->m_oError->add(self::ERR_USERNAME_EXISTS);
            return true;
        }
        
        // Otherwise hash the password and create the user
        $strHashedPass = $this->hashPassword($this->m_aInput["user"]["pass"]);
        $oUserTable->createUser(
                $this->m_aInput["user"]["name"], 
                $strHashedPass);

        // Return if there were errors
        if($oUserTable->m_oError->hasError())
        {
            $this->m_oError->addAll($oUserTable->m_oError->get());
            return false;
        }

        // Otherwise, create a user sessiona and return user data. Return if
        // any errors arise in the process
        $mResult = $oUserTable->getUser($this->m_aInput["user"]["name"]);
        if(!empty($mResult["id"]) && !empty($mResult["username"]))
        {
            $this->m_oUser->createSession($mResult["id"], $mResult["username"], false);
            if($this->m_oUser->m_oError->hasError())
            {
                $this->m_oError->addAll($this->m_oUser->m_oError->get());
                return false;
            }

            $this->m_mData = $this->m_oUser->get();
        }
        
        return true;
    }
    
    private function hashPassword($strPass){
        $strSHA256  = base64_encode(hash('sha256', $strPass, true));
        return password_hash($strSHA256, PASSWORD_BCRYPT);
    }
}
