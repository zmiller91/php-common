<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Logout
 *
 * @author zmiller
 */
class Logout extends Service
{
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
        if(empty($this->m_oUser->m_aCookie))
        {
            $bSuccess = false;
            $this->setStatusCode(403);
            $this->m_oError->add("No authorization cookie set");
        }
        
        return $bSuccess;
    }

    protected function post()
    {
        $this->m_oUser->removeSession();
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
