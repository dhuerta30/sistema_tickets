<?php

Class ArtifyErrorCtrl {

    private $errors = array();

    function addError($error, $override = false) {
        $this->errors[] = $error;
        if($override){
            echo $error;
            die();
        }
            
    }

    function getErrors() {
        return $this->errors;
    }

}
