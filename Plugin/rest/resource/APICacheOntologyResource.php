<?php

namespace Drupal\oeaw\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
// our drupal custom libraries

//ARCHE ACDH libraries
use EasyRdf\Resource;

/**
 * Provides an Publications Checker Resource.
 *
 * @RestResource(
 *   id = "api_cacheontology",
 *   label = @Translation("ARCHE Caching Ontology"),
 *   uri_paths = {
 *     "canonical" = "/api/ontology/"
 *   }
 * )
 */
class APICacheOntologyResource extends ResourceBase {
    /**
     * Responds to entity GET requests.
     * 
     * @return \Drupal\rest\ResourceResponse
     */
    public function get() {
        $response = array();

        $PTC = new \Drupal\oeaw\PropertyTableCache();
        if(true == $PTC->setCacheData()){
            $response = json_encode('cache updated succesfully!');
        }else {
            $response = json_encode('there is no ontology data to cache!');
        }
        return new ResourceResponse($response);
    }
}

