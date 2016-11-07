<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Authenticate
 *
 * @author zmiller
 */
class Authorize extends Service
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
        $this->m_oUser->authorize();
        return true;
    }

    protected function validate() 
    {
        return true;
    }
    
    protected function post()
    {
        // By default the service will try to authorize the user, just return
        // the user object because it's already been authorized!
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
