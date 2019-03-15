<?php

namespace Drupal\oeaw\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
// our drupal custom libraries
use Drupal\oeaw\Model\OeawStorage;
use Drupal\oeaw\Model\OeawCustomSparql;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
//ARCHE ACDH libraries
use acdhOeaw\util\RepoConfig as RC;
use EasyRdf\Resource;

/**
 * Provides an Places Checker Resource.
 *
 * @RestResource(
 *   id = "api_places",
 *   label = @Translation("ARCHE Places Checker"),
 *   uri_paths = {
 *     "canonical" = "/api/places/{data}"
 *   }
 * )
 */
class ApiPlacesResource extends ResourceBase {
    /**
     * Responds to entity GET requests.
     * 
     * @param string $data
     *
     * @return Response|JsonResponse
     */
    public function get(string $data) {
        $response = new Response();

        if(empty($data)){
            return new JsonResponse(array('Please provide a link'), 404, ['Content-Type' => 'application/json']);
        }

        $data = strtolower($data);

        $sparql = '';
        $spRes = array();
        $result = array();

        $OeawCustomSparql = new OeawCustomSparql();
        $OeawStorage = new OeawStorage();

        $sparql = $OeawCustomSparql->createBasicApiSparql($data, RC::get('drupalPlace'));

        if($sparql){
            $spRes = $OeawStorage->runUserSparql($sparql);

            if(count($spRes) > 0){
                for ($x = 0; $x < count($spRes); ++$x) {
                    $ids = array();
                    $ids = explode(',', $spRes[$x]['identifiers']);
                    //set the flag to false
                    $idContains = false;
                    foreach ($ids as $id){
                        $id = str_replace(RC::get('fedoraIdNamespace'), '', $id);
                        //if one of the identifier is contains the searched value
                        if (false !== strpos(strtolower($id), strtolower($data))) {
                            $idContains = true;
                        }
                    }

                    $uri = str_replace(strtolower(RC::get('fedoraVocabsNamespace')), '', strtolower($spRes[$x]['uri']) );
                    $urlContains = false;
                    if (false !== strpos($uri, $data)) {
                        $urlContains = true;
                    }

                    $titleContains = false;
                    if (false !== strpos(strtolower($spRes[$x]['title']), strtolower($data) )) {
                        $titleContains = true;
                    }

                    $altTitleContains = false;
                    if (false !== strpos(strtolower($spRes[$x]['altTitle']), strtolower($data) )) {
                        $altTitleContains = true;
                    }

                    if(true === $idContains || true === $urlContains || true === $titleContains || true === $altTitleContains){
                        $result[$x]['uri'] = $spRes[$x]['uri'];
                        $result[$x]['title'] = $spRes[$x]['title'];
                        $result[$x]['altTitle'] = $spRes[$x]['altTitle'];
                        $result[$x]['identifiers'] = explode(',', $spRes[$x]['identifiers']);
                    }
                }

                if(count($result) > 0){
                    $response->setContent(json_encode($result));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                }else{
                    return new JsonResponse(array('There is no resource'), 404, ['Content-Type' => 'application/json']);
                }
            }else {
                return new JsonResponse(array('There is no resource'), 404, ['Content-Type' => 'application/json']);
            }
        }else {
            return new JsonResponse(array('There is no resource'), 404, ['Content-Type' => 'application/json']);
        }
    }
}
