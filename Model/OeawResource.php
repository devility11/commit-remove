<?php

namespace Drupal\oeaw\Model;

use acdhOeaw\util\RepoConfig as RC;

/**
 * This object is contains the necessary data for the oeaw_detail Resource.
 * Also the special views and child elements will also use this object to 
 * create their own data.
 */
class OeawResource {
    private $uri = '';
    private $insideUri = '';
    private $fedoraUri = '';
    private $identifiers = array();
    private $title = '';
    private $pid = '';
    private $type = '';
    private $typeUri = '';
    private $imageUrl = '';
    private $availableDate = '';
    private $highlighting = array();
    private $accessRestriction = 'public';
    private $table = array();
    public $errors = array();

    /**
     * Set up the properties and init the obj.
     * 
     * @param \ArrayObject $arrayObj
     * @param type         $cfg
     *
     * @throws \ErrorException
     */
    public function __construct(\ArrayObject $arrayObj, $cfg = null) {
        if(null == $cfg){
            \acdhOeaw\util\RepoConfig::init($_SERVER['DOCUMENT_ROOT'].'/modules/oeaw/config.ini');
        } else {
            \acdhOeaw\util\RepoConfig::init($cfg);
        }

        if (is_object($arrayObj) || !empty($arrayObj)) {
            $objIterator = $arrayObj->getIterator();

            while($objIterator->valid()) {
                ('uri' == $objIterator->key()) ? $this->uri = $objIterator->current() : NULL;
                ('insideUri' == $objIterator->key()) ? $this->insideUri = $objIterator->current() : NULL;
                ('fedoraUri' == $objIterator->key()) ? $this->fedoraUri = $objIterator->current() : NULL;
                ('identifiers' == $objIterator->key()) ? $this->identifiers = $objIterator->current() : NULL;
                ('title' == $objIterator->key()) ? $this->title = $objIterator->current() : NULL;
                ('pid' == $objIterator->key()) ? $this->pid = $objIterator->current() : NULL;
                ('type' == $objIterator->key()) ? $this->type = $objIterator->current() : NULL;
                ('typeUri' == $objIterator->key()) ? $this->typeUri = $objIterator->current() : NULL;
                ('imageUrl' == $objIterator->key()) ? $this->imageUrl = $objIterator->current() : NULL;
                ('availableDate' == $objIterator->key()) ? $this->availableDate = $objIterator->current() : NULL;
                ('accessRestriction' == $objIterator->key()) ? $this->accessRestriction = $objIterator->current() : 'public';
                ('highlighting' == $objIterator->key()) ? $this->highlighting = $objIterator->current() : NULL;
                ('table' == $objIterator->key()) ? $this->table = $objIterator->current() : NULL;

                $objIterator->next();
            }
        }else {
            throw new \ErrorException(t('ArrayObject').' '.t('Error').' -> OeawResource construct');
        }

        $this->checkEmptyVariables();
        if(count($this->errors) > 0){
            throw new \ErrorException(
                t('Init').' '.t('Error').' : OeawResource.'.' '.t(' Empty').' '.t('Data').': '.print_r($this->errors, true)
            );
        }
    }

    /**
     *  Check the necessary properties for the obj init.
     */
    private function checkEmptyVariables() {
        if(empty($this->uri)){ array_push($this->errors, 'uri'); }
        if(empty($this->title)){ array_push($this->errors, 'title'); }
        if(empty($this->insideUri)){ array_push($this->errors, 'insideUri'); }
        if(empty($this->fedoraUri)){ array_push($this->errors, 'fedoraUri'); }
        if(empty($this->identifiers)){ array_push($this->errors, 'identifiers'); }
        if(empty($this->title)){ array_push($this->errors, 'title'); }
        if(empty($this->type)){ array_push($this->errors, 'type'); }
        if(empty($this->typeUri)){ array_push($this->errors, 'typeUri'); }
        if(empty($this->table)){ array_push($this->errors, 'table'); }
    }

    /**
     * Resource URI.
     *
     * @return type
     */
    public function getUri(){
        return $this->uri;
    }

    /**
     * ARCHE supported inside url for the detail view display.
     *
     * @return type
     */    
    public function getInsideUri(): string{
        return $this->insideUri;
    }

    public function getIdentifiers(): array{
        return $this->identifiers;
    }

    public function getAcdhIdentifier(): string{
        if (count($this->identifiers) > 0){
            $uuid = '';
            foreach($this->identifiers as $id){
                if (false !== strpos($id, RC::get('fedoraUuidNamespace'))) {
                    $uuid = $id;
                    //if the identifier is the normal acdh identifier then return it
                }else if (false !== strpos($id, RC::get('fedoraIdNamespace'))) {
                    $this->insideUri = $id;
                    return $id;
                }
            }
            if(!empty($uuid)){
                return $uuid;
            }
        }
        return '';
    }

    public function getFedoraUri(): string {
        return $this->fedoraUri;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getTypeUri(): string {
        return $this->typeUri;
    }

    public function getImageUrl(): string {
        return $this->imageUrl;
    }

    public function getPID(): string {
        return $this->pid;
    }

    public function getTable(): array {
        return $this->table;
    }

    public function getAvailableDate(): string {
        return $this->availableDate;
    }

    public function getAccessRestriction(): string {
        if( ('collection' == strtolower($this->getType())) ||
            ('resource' == strtolower($this->getType())) ||
            ('metadata' == strtolower($this->getType())) ){
            return $this->accessRestriction;
        }
        return '';
    }

    /**
     * Get the property based info from the object table
     * The properties are in a shortcut format in the table
     * F.e: acdh:hasAvailableDate.
     * 
     * @param string $prop
     *
     * @return type
     */
    public function getTableData(string $prop){
        if(isset($this->table[$prop])){
            return $this->table[$prop];
        }
    }

    public function setTableData(string $prop, array $data): bool{
        if(isset($this->table[$prop])){
            $this->table[$prop] = $data;
            return true;
        }else{
            return false;
        }
    }

    public function getHighlighting(): array{
        return $this->highlighting;
    }
}
