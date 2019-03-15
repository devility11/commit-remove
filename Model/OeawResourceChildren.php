<?php

namespace Drupal\oeaw\Model;

/**
 * This object will contains the oeaw_detail Resource Child elements.
 */
class OeawResourceChildren {
    private $uri = '';
    private $title = '';
    private $pid = '';
    private $description = '';
    private $typeUri = '';
    private $identifier = '';
    private $insideUri = '';
    private $typeName = '';
    private $accessRestriction = 'public';
    public $errors = array();

    /**
     * Set up the properties and init the obj.
     * 
     * @param \ArrayObject $arrayObj
     *
     * @throws \ErrorException
     */
    public function __construct(\ArrayObject $arrayObj) {
        if (is_object($arrayObj) || !empty($arrayObj)) {
            $objIterator = $arrayObj->getIterator();

            while($objIterator->valid()) {
                ('uri' == $objIterator->key()) ? $this->uri = $objIterator->current() : NULL;
                ('title' == $objIterator->key()) ? $this->title = $objIterator->current() : NULL;
                ('pid' == $objIterator->key()) ? $this->pid = $objIterator->current() : NULL;
                ('description' == $objIterator->key()) ? $this->description = $objIterator->current() : NULL;
                ('typeUri' == $objIterator->key()) ? $this->typeUri = $objIterator->current() : NULL;
                ('identifier' == $objIterator->key()) ? $this->identifier = $objIterator->current() : NULL;
                ('insideUri' == $objIterator->key()) ? $this->insideUri = $objIterator->current() : NULL;
                ('typeName' == $objIterator->key()) ? $this->typeName = $objIterator->current() : NULL;
                ('accessRestriction' == $objIterator->key()) ? $this->accessRestriction = $objIterator->current() : 'public';
                $objIterator->next();
            }
        }else {
            throw new \ErrorException(t('ArrayObject').' '.t('Error').' -> OeawResourceChildren construct');
        }

        $this->checkEmptyVariables();
        if(count($this->errors) > 0){
            throw new \ErrorException(
                t('Init').' '.t('Error').' : OeawResourceChildren.'.' '.t(' Empty').' '.t('Data').': '.print_r($this->errors, true)
            );
        }
    }

    /**
     *  Check the necessary properties for the obj init.
     */
    private function checkEmptyVariables() {
        if(empty($this->uri)){ array_push($this->errors, 'uri'); }
        if(empty($this->title)){ array_push($this->errors, 'title'); }
        //if(empty($this->description)){ array_push($this->errors, "description");  }
        if(empty($this->typeUri)){ array_push($this->errors, 'typeUri'); }
        if(empty($this->typeName)){ array_push($this->errors, 'typeName'); }
        if(empty($this->identifier)){ array_push($this->errors, 'identifier'); }
        if(empty($this->insideUri)){ array_push($this->errors, 'insideUri'); }
    }

    public function getUri(): string {
        return $this->uri;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function getPid(): string {
        return $this->pid;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getTypeUri(): string{
        return $this->typeUri;
    }

    public function getIdentifier(){
        return $this->identifier;
    }

    public function getInsideUri(){
        return $this->insideUri;
    }

    public function getTypeName(){
        return $this->typeName;
    }

    public function getAccessRestriction(){
        if( ('collection' == strtolower($this->getTypeName())) ||
            ('resource' == strtolower($this->getTypeName())) ||
            ('metadata' == strtolower($this->getTypeName())) ){
            return $this->accessRestriction;
        }
        return '';
    }
}
