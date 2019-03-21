<?php

namespace Drupal\oeaw\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Archiver\Zip;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Language\LanguageInterface;

use Drupal\oeaw\Model\OeawStorage;
use Drupal\oeaw\Model\OeawResource;
use Drupal\oeaw\Model\OeawResourceDetails;
use Drupal\oeaw\Model\OeawCustomSparql;

use Drupal\oeaw\OeawFunctions;
use Drupal\oeaw\Helper\Helper;
use Drupal\oeaw\BreadcrumbCache;
use Drupal\oeaw\PropertyTableCache;
use Drupal\Core\Cache\CacheBackendInterface;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

use acdhOeaw\fedora\dissemination\Service as Service;
use acdhOeaw\util\RepoConfig as RC;
use Drupal\oeaw\ConfigConstants as CC;
use acdhOeaw\fedora\Fedora;
use acdhOeaw\fedora\FedoraResource;
use EasyRdf\Graph;
use EasyRdf\Resource;

use TCPDF;

use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Render\HtmlResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

use GuzzleHttp\Client;

/**
 * Class FrontendController
 *
 */
class FrontendController extends ControllerBase
{
    use StringTranslationTrait;
    private $oeawStorage;
    private $oeawFunctions;
    private $oeawCustomSparql;
    private $propertyTableCache;
    private $uriFor3DObj;
    private $langConf;
    
    /**
     * Set up the necessary properties and config
     */
    public function __construct()
    {
        $this->oeawStorage = new OeawStorage();
        $this->oeawFunctions = new OeawFunctions();
        $this->oeawCustomSparql = new OeawCustomSparql();
        $this->propertyTableCache = new PropertyTableCache();
        \acdhOeaw\util\RepoConfig::init($_SERVER["DOCUMENT_ROOT"].'/modules/oeaw/config.ini');
        $this->langConf = $this->config('oeaw.settings');
    }

    /**
     *
     * The root Resources list
     *
     * @param int $limit Amount of resources to get
     * @param int $page nth Page for pagination
     * @param string $order Order resources by, usage: ASC/DESC(?property)
     *
     * @return array
     */
    public function roots_list(string $limit = "10", string $page = "1", string $order = "datedesc"): array
    {
        drupal_get_messages('error', true);
        // get the root resources
        // sparql result fields - uri, title
        $result = array();
        $datatable = array();
        $res = array();
        $uid = \Drupal::currentUser()->id();
        $limit = (int)$limit;
        $page = (int)$page;
        $page = $page-1;
        
        //count all root resource for the pagination
        try {
            $countRes = $this->oeawStorage->getRootFromDB(0, 0, true);
        } catch (\Exception $ex) {
            drupal_set_message($ex->getMessage(), 'error');
            return array();
        } catch (\InvalidArgumentException $ex) {
            drupal_set_message($ex->getMessage(), 'error');
            return array();
        }
        
        $countRes = $countRes[0]["count"];
        if ($countRes == 0) {
            drupal_set_message(
                $this->langConf->get('errmsg_no_root_resources') ? $this->langConf->get('errmsg_no_root_resources') : 'You have no Root resources',
                'error',
                false
            );
            return array();
        }
        $search = array();
        //create data for the pagination
        $pageData = $this->oeawFunctions->createPaginationData($limit, $page, $countRes);
        $pagination = "";
        if ($pageData['totalPages'] > 1) {
            $pagination =  $this->oeawFunctions->createPaginationHTML($page, $pageData['page'], $pageData['totalPages'], $limit);
        }

        //Define offset for pagination
        if ($page > 0) {
            $offsetRoot = $page * $limit;
        } else {
            $offsetRoot = 0;
        }

        try {
            $result = $this->oeawStorage->getRootFromDB($limit, $offsetRoot, false, $order);
        } catch (Exception $ex) {
            drupal_set_message($ex->getMessage(), 'error');
            return array();
        } catch (\InvalidArgumentException $ex) {
            drupal_set_message($ex->getMessage(), 'error');
            return array();
        }
        
        if (count($result) > 0) {
            foreach ($result as $value) {
                $tblArray = array();
                
                $arrayObject = new \ArrayObject();
                $arrayObject->offsetSet('title', $value['title']);
                
                $resourceIdentifier = $this->oeawFunctions->createDetailViewUrl($value);
                $arrayObject->offsetSet('uri', $resourceIdentifier);
                $arrayObject->offsetSet('fedoraUri', $value['uri']);
                $arrayObject->offsetSet('insideUri', $this->oeawFunctions->detailViewUrlDecodeEncode($resourceIdentifier, 1));
                $arrayObject->offsetSet('identifiers', $value['identifier']);
                $arrayObject->offsetSet('pid', $value['pid']);
                $arrayObject->offsetSet('type', str_replace(RC::get('fedoraVocabsNamespace'), '', $value['acdhType']));
                $arrayObject->offsetSet('typeUri', $value['acdhType']);
                $arrayObject->offsetSet('availableDate', $value['availableDate']);
                $arrayObject->offsetSet('accessRestriction', $value['accessRestriction']);
                
                if (isset($value['contributor']) && !empty($value['contributor'])) {
                    $contrArr = explode(',', $value['contributor']);
                    $tblArray['contributors'] = $this->oeawFunctions->createContribAuthorData($contrArr);
                }
                if (isset($value['author']) && !empty($value['author'])) {
                    $authArr = explode(',', $value['author']);
                    $tblArray['authors'] = $this->oeawFunctions->createContribAuthorData($authArr);
                }
                
                if (isset($value['image']) && !empty($value['image'])) {
                    $arrayObject->offsetSet('imageUrl', $value['image']);
                } elseif (isset($value['hasTitleImage']) && !empty($value['hasTitleImage'])) {
                    $imageUrl = $this->oeawStorage->getImageByIdentifier($value['hasTitleImage']);
                    if ($imageUrl) {
                        $arrayObject->offsetSet('imageUrl', $imageUrl);
                    }
                }
                
                if (isset($value['description']) && !empty($value['description'])) {
                    $tblArray['description'] = $value['description'];
                }
                
                if (count($tblArray) == 0) {
                    $tblArray['title'] = $value['title'];
                }
                
                $arrayObject->offsetSet('table', $tblArray);
           
                try {
                    $obj = new \Drupal\oeaw\Model\OeawResource($arrayObject);
                    $res[] = $obj;
                } catch (\ErrorException $ex) {
                    throw new \ErrorException(t('Error message').':  FrontendController -> OeawResource Exception ');
                }
            }
        } else {
            drupal_set_message(
                $this->langConf->get('errmsg_no_root_resources') ? $this->langConf->get('errmsg_no_root_resources') : 'You have no Root resources',
                'error',
                false
            );
            return array();
        }
        
        //create the datatable values and pass the twig template name what we want to use
        $datatable = array(
            '#userid' => $uid,
            '#attached' => [
                'library' => [
                'oeaw/oeaw-styles',
                ]
            ]
        );
        
        if (count((array)$res) > 0) {
            //$header = array_keys($res[0]);
            $datatable['#theme'] = 'oeaw_complex_search_res';
            $datatable['#result'] = $res;
            $datatable['#search'] = $search;
            $datatable['#pagination'] = $pagination;
            //$datatable['#searchedValues'] = $i . ' top-level elements have been found.';
            $datatable['#totalResultAmount'] = $countRes;
            if (empty($pageData['page']) or $pageData['page'] == 0) {
                $datatable['#currentPage'] = 1;
            } else {
                $datatable['#currentPage'] = $pageData['page'] + 1;
            }
            if (empty($pageData) or $pageData['totalPages'] == 0) {
                $datatable['#totalPages'] = 1;
            } else {
                $datatable['#totalPages'] = $pageData['totalPages'];
            }
        }

        return $datatable;
    }
    
    
    /**
     *
     * The acdh:query display page with the user defined sparql query
     *
     * @param string $uri
     * @return array
     */
    public function oeaw_query(string $uri)
    {
        if (empty($uri)) {
            return drupal_set_message(
               $this->langConf->get('errmsg_resource_not_exists') ? $this->langConf->get('errmsg_resource_not_exists') : 'Resource does not exist!',
               'error'
           );
        }
        
        $uri = base64_decode($uri);
        $data = array();
        $userSparql = array();
        $errorMSG = "";
        $header = array();
        
        $data = $this->oeawStorage->getValueByUriProperty($uri, \Drupal\oeaw\ConfigConstants::$acdhQuery);
        
        if (isset($data)) {
            $userSparql = $this->oeawStorage->runUserSparql($data[0]['value']);
            
            if (count($userSparql) > 0) {
                $header = $this->oeawFunctions->getKeysFromMultiArray($userSparql);
            }
        }
        
        if (count($userSparql) == 0) {
            $errorMSG = "Sparql query has no result";
        }

        $uid = \Drupal::currentUser()->id();
        // decode the uri hash
        
        $datatable = array(
            '#theme' => 'oeaw_query',
            '#result' => $userSparql,
            '#header' => $header,
            '#userid' => $uid,
            '#errorMSG' => $errorMSG,
            '#attached' => [
                'library' => [
                'oeaw/oeaw-styles',
                ]
            ]
        );
        
        return $datatable;
    }
   
    /**
     * The detail view of the Resource
     *
     * @param string $res_data
     * @return array
     */
    public function oeaw_detail(string $res_data)
    {
        drupal_get_messages('error', true);
        $rules = array();
        $ACL = array();
        $fedoraRes = array();
        $breadcumb = array();
        $response = "html";
        
        //we have the url and limit page data in the string
        if (empty($res_data)) {
            drupal_set_message(
                $this->langConf->get('errmsg_resource_not_exists') ? $this->langConf->get('errmsg_resource_not_exists') : 'Resource does not exist',
                'error'
            );
            return array();
        }
        //if we have ajax div reload
        if (strpos($res_data, 'ajax=1') !== false) {
            $response = "ajax";
        }
        
        $identifier = "";
        //transform the url from the browser to readable uri
        $identifier = $this->oeawFunctions->detailViewUrlDecodeEncode($res_data, 0);
        if (empty($identifier)) {
            drupal_set_message($this->langConf->get('errmsg_resource_not_exists') ? $this->langConf->get('errmsg_resource_not_exists') : 'Resource does not exist', 'error');
            return array();
        }
        $limitAndPage = $this->oeawFunctions->getLimitAndPageFromUrl($res_data);
        $page = 1;
        $limit = 10;
        
        if (count($limitAndPage) > 0) {
            if (isset($limitAndPage['page'])) {
                $page = $limitAndPage['page'];
            }
            if (isset($limitAndPage['limit'])) {
                $limit = $limitAndPage['limit'];
            }
        }
        
        //if the browser url contains handle url then we need to get the acdh:hasIdentifier
        if (strpos($identifier, 'hdl.handle.net') !== false) {
            $identifier = $this->oeawFunctions->pidToAcdhIdentifier($identifier);
        }
        
        $fedora = $this->oeawFunctions->initFedora();
        $uid = \Drupal::currentUser()->id();
        
        //get the resource metadata
        try {
            $fedoraRes = $fedora->getResourceById($identifier);
            $rootMeta = $fedoraRes->getMetadata();
            /*if($rootMeta->getUri()) {
                $turtle = $this->oeawFunctions->turtleDissService($rootMeta->getUri());

            }*/
            //
        } catch (\acdhOeaw\fedora\exceptions\NotFound $ex) {
            drupal_set_message(
                $this->langConf->get('errmsg_fedora_exception') ? $this->langConf->get('errmsg_fedora_exception').' :getMetadata function' : 'Fedora Exception : getMetadata function',
                'error'
            );
            return array();
        } catch (\GuzzleHttp\Exception\ClientException $ex) {
            drupal_set_message(t($ex->getMessage()), 'error');
            return array();
        }
        
        //get the actual resource rules
        try {
            $rules = $this->oeawFunctions->getRules($identifier, $fedoraRes);
        } catch (Exception $ex) {
            drupal_set_message(t($ex->getMessage()), 'error');
            return array();
        } catch (\acdhOeaw\fedora\exceptions\NotFound $ex) {
            drupal_set_message(t($ex->getMessage()), 'error');
            return array();
        }
        
        if (count((array)$rootMeta)) {
            //create the OEAW resource Object for the GUI data
            try {
                $resultsObj = $this->oeawFunctions->createDetailViewTable($rootMeta);
            } catch (\ErrorException $ex) {
                drupal_set_message(t("Error").' : '.$ex->getMessage(), 'error');
                return array();
            }
            
            try {
                //$results['ACL'] = $this->oeawFunctions->checkRules($rules);
            } catch (Exception $ex) {
                drupal_set_message($ex->getMessage(), 'error');
                return array();
            }
            
            //check the acdh:hasIdentifier data to the child view
            if (count($resultsObj->getIdentifiers()) > 0) {
                $customDetailView = array();
                //if we have a type and this type can found in the available custom views array
                try {
                    $customDetailView = $this->oeawFunctions->createCustomDetailViewTemplateData($resultsObj, $resultsObj->getType());
                } catch (\ErrorException $ex) {
                    drupal_set_message(t("Error message").' : Resource Custom Table View. '.$ex->getMessage(), 'error');
                    return array();
                }
                if (count((array)$customDetailView) > 0) {
                    $extras['specialType'][strtolower($resultsObj->getType())] = $customDetailView;
                }
            }
        } else {
            drupal_set_message(
                $this->langConf->get('errmsg_resource_no_metadata') ? $this->langConf->get('errmsg_resource_no_metadata') : 'The resource has no metadata!',
                'error'
            );
            return array();
        }
        /*
        $query = "";
        if(isset($results['query']) && isset($results['queryType'])){
            if($results['queryType'] == "SPARQL"){
                $query = base64_encode($uri);
            }
        }
        */
        
        //the breadcrumb section
        if ($resultsObj->getType() == "Collection" || $resultsObj->getType() == "Resource"
                || $resultsObj->getType() == "Metadata") {
            $breadcrumbs = array();
            
            $breadcrumbCache = new BreadcrumbCache();
            //we have cached breadcrumbs with this identifier
            if (count($breadcrumbCache->getCachedData($identifier)) > 0) {
                $extras['breadcrumb'] = $breadcrumbCache->getCachedData($identifier);
            } else {
                $breadcrumbs = $breadcrumbCache->setCacheData($identifier);
                if (count($breadcrumbs) > 0) {
                    $extras['breadcrumb'] = $breadcrumbs;
                }
            }
        }
        $dissServices = array();
        //check the Dissemination services
        try {
            $dissServices = $this->oeawFunctions->getResourceDissServ($fedoraRes);
        } catch (Exception $ex) {
            drupal_set_message($ex->getMessage(), 'error');
            return array();
        } catch (\acdhOeaw\fedora\exceptions\NotFound $ex) {
            drupal_set_message($ex->getMessage(), 'error');
            return array();
        }
        if (count($dissServices) > 0 && $fedoraRes->getId()) {
            //we need to remove the raw from the list if it is a collection
            if ($resultsObj->getType() == "Collection") {
                for ($i=0; $i <= count($dissServices); $i++) {
                    if ($dissServices[$i]['returnType'] == "raw") {
                        unset($dissServices[$i]);
                        break;
                    }
                }
            }
            $extras['dissServ']['services'] = $dissServices;
            $extras['dissServ']['identifier'] = $fedoraRes->getId();
        }
        // Pass fedora uri so it can be linked in the template
        $extras["fedoraURI"] = $rootMeta->getUri();
        
        //format the hasavailable date
        if (!empty($resultsObj->getTableData("acdh:hasAvailableDate"))) {
            $avDate = $resultsObj->getTableData("acdh:hasAvailableDate");
            if (is_array($avDate)) {
                $avDate = $avDate[0];
            }
            if (\DateTime::createFromFormat('Y-d-d', $avDate) !== false) {
                $time = strtotime($avDate);
                $newTime = date('Y-m-d', $time);
                if ($resultsObj->setTableData("acdh:hasAvailableDate", array($newTime)) == false || empty($newTime)) {
                    drupal_set_message(t('Error').' : Available date format', 'error');
                    return array();
                }
            }
            //if we dont have a real date just a year
            if (\DateTime::createFromFormat('Y', $avDate) !== false) {
                $year = \DateTime::createFromFormat('Y', $avDate);
                if ($resultsObj->setTableData("acdh:hasAvailableDate", array($year->format('Y'))) == false || empty($year->format('Y'))) {
                    drupal_set_message(t('Error').' : Available date format', 'error');
                    return array();
                }
            }
        }
        
        //generate the NiceUri to the detail View
        $niceUri = "";
        $niceUri = Helper::generateNiceUri($resultsObj);
        if (!empty($niceUri)) {
            $extras["niceURI"] = $niceUri;
        }
        
        //Create data for cite-this widget
        $typesToBeCited = ["collection", "project", "resource", "publication"];
        if (!empty($resultsObj->getType()) && in_array(strtolower($resultsObj->getType()), $typesToBeCited)) {
            //pass $rootMeta for rdf object
            $extras["CiteThisWidget"] = $this->oeawFunctions->createCiteThisWidget($resultsObj);
        }
        
        //get the tooltip from cache
        $cachedTooltip = $this->propertyTableCache->getCachedData($resultsObj->getTable());
        if (count($cachedTooltip) > 0) {
            $extras["tooltip"] = $cachedTooltip;
        }
        
        //if it is a resource then we need to check the 3dContent
        if ($resultsObj->getType() == "Resource") {
            if (Helper::check3dData($resultsObj->getTable()) === true) {
                $extras['3dData'] = true;
            } else {
                //but if we have resource and the diss-serv contains the 3d viewer, but our viewer cant show it, then we need to remove
                // the 3d viewer from the dissemination services.
                if (array_search('https://id.acdh.oeaw.ac.at/dissemination/3DObject', array_column($dissServices, 'identifier')) !== false) {
                    $key = array_search('https://id.acdh.oeaw.ac.at/dissemination/3DObject', array_column($dissServices, 'identifier'));
                    unset($dissServices[$key]);
                }
            }
        }
        $datatable = array(
                '#theme' => 'oeaw_detail_dt',
                '#result' => $resultsObj,
                '#extras' => $extras,
                '#userid' => $uid,
                '#attached' => [
                    'library' => [
                    'oeaw/oeaw-styles', //include our custom library for this response
                    ]
                ]
        );
        //for the ajax oeaw_detail view page refresh we need to send a response
        //othwerwise itt will post the whole page
        if ($response == "ajax") {
            return new Response(render($datatable));
        }
        
        return $datatable;
    }
   
    /**
     * Change language session variable API
     * Because of the special path handling, the basic language selector is not working
     *
     * @param string $lng
     * @return Response
    */
    public function oeaw_change_lng(string $lng = 'en'): Response
    {
        $_SESSION['language'] = strtolower($lng);
        $response = new Response();
        $response->setContent(json_encode("language changed to: ".$lng));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
   
    /**
     * This API will generate the child html view.
     *
     * @param string $identifier - the UUID
     * @param string $page
     * @param string $limit
     */
    public function oeaw_child_api(string $identifier, string $limit, string $page, string $order): Response
    {
        if (strpos($identifier, RC::get('fedoraUuidNamespace')) === false) {
            $identifier = RC::get('fedoraUuidNamespace').$identifier;
        }
        $childArray = $this->oeawFunctions->generateChildAPIData($identifier, (int)$limit, (int)$page, $order);
         
        if (count($childArray['childResult']) == 0) {
            $childArray['errorMSG'] =
                $this->langConf->get('errmsg_no_child_resources') ? $this->langConf->get('errmsg_no_child_resources') : 'There are no Child resources';
        }
        
        $childArray['language'] = \Drupal::languageManager()->getCurrentLanguage()->getId();
        
        $build = [
            '#theme' => 'oeaw_child_view',
            '#result' => $childArray,
            '#attached' => [
                'library' => [
                    'oeaw/oeaw-styles', //include our custom library for this response
                ]
            ]
        ];
        
        return new Response(render($build));
    }
    
    /**
     * This API will generate the turtle file from the resource.
     *
     * @param string $identifier - the UUID
     * @param string $page
     * @param string $limit
     */
    public function oeaw_turtle_api(string $identifier): Response
    {
        $identifier = $this->oeawFunctions->detailViewUrlDecodeEncode($identifier, 0);
        $fedoraUrl = "";
        $fedoraUrl = $this->oeawStorage->getFedoraUrlByIdentifierOrPid($identifier);
        
        if (!empty($fedoraUrl)) {
            $result = $this->oeawFunctions->turtleDissService($fedoraUrl);
            return new Response($result, 200, ['Content-Type'=> 'text/turtle']);
        }
        return new Response("No data!", 400);
    }
   
    /**
     *
     * The complex search frontend function
     *
     * @param string $metavalue
     * @param string $limit
     * @param string $page
     * @param string $order
     * @return array
     * @throws \ErrorException
     */
    public function oeaw_complexsearch(string $metavalue = "root", string $limit = "10", string $page = "1", string $order = "datedesc"): array
    {
        drupal_get_messages('error', true);
       
        if (empty($metavalue)) {
            $metavalue = "root";
        }
        
        //If the discover page calls the root resources forward to the root_list method
        if ($metavalue == 'root') {
            //If a cookie setting exists and the query is coming without a specific parameter
            if ((isset($_COOKIE["resultsPerPage"]) && !empty($_COOKIE["resultsPerPage"])) && empty($limit)) {
                $limit = $_COOKIE["resultsPerPage"];
            }
            if ((isset($_COOKIE["resultsOrder"]) && !empty($_COOKIE["resultsOrder"])) && empty($order)) {
                $order = $_COOKIE["resultsOrder"];
            }
            if (empty($page)) {
                $page = "1";
            }
            return $this->roots_list($limit, $page, $order);
        } else {
            $res = array();
            $errorMSG = array();
            //Deduct 1 from the page since the backend works with 0 and the frontend 1 for the initial page
            $page = (int)$page - 1;
            $limit = (int)$limit;
            $result = array();
            $solrData = array();
            $pagination = "";
            //get the current page for the pagination
            $currentPage = $this->oeawFunctions->getCurrentPageForPagination();

            $metavalue = urldecode($metavalue);
            $metavalue = str_replace(' ', '+', $metavalue);

            $searchStr = $this->oeawFunctions->explodeSearchString($metavalue);
            
            if (!in_array("", $searchStr) === false) {
                drupal_set_message(t("Your search yielded no results."), 'error');
                return array();
            }
            /** Check the the of the search, it is necessary for the solr search **/
            if (
                isset($searchStr['words']) &&
                (
                    (!isset($searchStr['type']))
                        ||
                    (isset($searchStr['type']) && strtolower($searchStr['type']) == "resource")
                )
            ) {
                $solrData = $this->oeawFunctions->getDataFromSolr($searchStr['words']);
            }
            
            try {
                $countSparql = $this->oeawCustomSparql->createFullTextSparql($searchStr, 0, 0, true);
            } catch (\ErrorException $ex) {
                drupal_set_message($ex->getMessage(), 'error');
                return array();
            }
            $solrCount = count($solrData);
            $count = $this->oeawStorage->runUserSparql($countSparql);

            $total = (int)count($count) + $solrCount;
            if ($total < 1) {
                drupal_set_message(t("Your search yielded no results."), 'error');
            }
            //create data for the pagination
            $pageData = $this->oeawFunctions->createPaginationData($limit, $page, $total);

            if ($pageData['totalPages'] > 1) {
                $pagination =  $this->oeawFunctions->createPaginationHTML($currentPage, $pageData['page'], $pageData['totalPages'], $limit);
            }
            try {
                $sparql = $this->oeawCustomSparql->createFullTextSparql($searchStr, $limit, $pageData['end'], false, $order);
                $res = $this->oeawStorage->runUserSparql($sparql);
            } catch (\ErrorException $ex) {
                drupal_set_message($ex->getMessage(), 'error');
                return array();
            }
           
            if ($solrCount > 0) {
                $res = array_merge($res, $solrData);
            }

            if (count($res) > 0) {
                foreach ($res as $r) {
                    if ((isset($r['title']) && !empty($r['title']))
                            && (isset($r['uri']) && !empty($r['uri']))
                            && (isset($r['identifier']) && !empty($r['identifier']))
                            && (isset($r['acdhType']) && !empty($r['acdhType']))) {
                        $tblArray = array();

                        $arrayObject = new \ArrayObject();
                        $arrayObject->offsetSet('title', $r['title']);
                        $resourceIdentifier = $this->oeawFunctions->createDetailViewUrl($r);
                        $arrayObject->offsetSet('uri', $resourceIdentifier);
                        $arrayObject->offsetSet('fedoraUri', $r['uri']);
                        $arrayObject->offsetSet('insideUri', $this->oeawFunctions->detailViewUrlDecodeEncode($resourceIdentifier, 1));
                        $arrayObject->offsetSet('identifiers', $r['identifier']);
                        $arrayObject->offsetSet('pid', (isset($r['pid'])) ? $r['pid'] : "");
                        $arrayObject->offsetSet('type', str_replace(RC::get('fedoraVocabsNamespace'), '', $r['acdhType']));
                        $arrayObject->offsetSet('typeUri', $r['acdhType']);

                        if (isset($r['availableDate']) && !empty($r['availableDate'])) {
                            $arrayObject->offsetSet('availableDate', $r['availableDate']);
                        }
                        if (isset($r['accessRestriction']) && !empty($r['accessRestriction'])) {
                            $arrayObject->offsetSet('accessRestriction', $r['accessRestriction']);
                        }
                        if (isset($r['authors']) && !empty($r['authors'])) {
                            $authArr = explode(',', $r['authors']);
                            $tblArray['authors'] = $this->oeawFunctions->createContribAuthorData($authArr);
                        }
                        if (isset($r['contribs']) && !empty($r['contribs'])) {
                            $contrArr = explode(',', $r['contribs']);
                            $tblArray['contributors'] = $this->oeawFunctions->createContribAuthorData($contrArr);
                        }
                        
                        if (isset($r['highlighting']) && !empty($r['highlighting'])) {
                            $arrayObject->offsetSet('highlighting', $r['highlighting']);
                        }
                        
                        if (count($tblArray) == 0) {
                            $tblArray['title'] = $r['title'];
                        }
                        if (isset($r['image']) && !empty($r['image'])) {
                            $arrayObject->offsetSet('imageUrl', $r['image']);
                        } elseif (isset($r['hasTitleImage']) && !empty($r['hasTitleImage'])) {
                            $imageUrl = $this->oeawStorage->getImageByIdentifier($r['hasTitleImage']);
                            if ($imageUrl) {
                                $arrayObject->offsetSet('imageUrl', $imageUrl);
                            }
                        }
                        
                        if (isset($r['description']) && !empty($r['description'])) {
                            $tblArray['description'] = $r['description'];
                        }
                        $arrayObject->offsetSet('table', $tblArray);
                        try {
                            $obj = new \Drupal\oeaw\Model\OeawResource($arrayObject);
                            $result[] = $obj;
                        } catch (ErrorException $ex) {
                            throw new \ErrorException(t('Error').':'.__FUNCTION__, 'error');
                        }
                    }
                }
            }
            if (count($result) == 0) {
                return array();
            }
            
            $uid = \Drupal::currentUser()->id();

            $datatable['#theme'] = 'oeaw_complex_search_res';
            $datatable['#userid'] = $uid;
            $datatable['#pagination'] = $pagination;
            $datatable['#errorMSG'] = $errorMSG;
            $datatable['#result'] = $result;
            //$datatable['#searchedValues'] = $total . ' elements containing "' . $metavalue . '" have been found.';
            $datatable['#totalResultAmount'] = $total;

            if (empty($pageData['page']) or $pageData['page'] == 0) {
                $datatable['#currentPage'] = 1;
            } else {
                $datatable['#currentPage'] = $pageData['page'] + 1;
            }
            if (empty($pageData) or $pageData['totalPages'] == 0) {
                $datatable['#totalPages'] = 1;
            } else {
                $datatable['#totalPages'] = $pageData['totalPages'];
            }
            return $datatable;
        }
    }
   
    
    /**
     * cache the acdh ontology
     */
    public function oeaw_cache_ontology(): Response
    {
        $result = array();
        if ($this->propertyTableCache->setCacheData() == true) {
            $result = "cache updated succesfully!";
        } else {
            $result = "there is no ontology data to cache!";
        }
        
        $response = new Response();
        $response->setContent(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    /**
     *
     * This function is for the oeaw_detail view. to the user can get the inverse table data
     *
     * @param string $data - the resource url
     * @return Response
     */
    public function oeaw_inverse_result(string $data): Response
    {
        $invData = array();
        
        if (!empty($data)) {
            $identifier = $this->oeawFunctions->detailViewUrlDecodeEncode($data, 0);
            $fdUrlArr = array();
            $fdUrlArr = $this->oeawStorage->getTitleByIdentifier($identifier);
            if (count($fdUrlArr) > 0) {
                if (isset($fdUrlArr[0]['uri'])) {
                    $uri = $fdUrlArr[0]['uri'];
                    $res = $this->oeawStorage->getInverseViewDataByURL($uri);
            
                    if (count($res) <= 0) {
                        $invData["data"] = array();
                    } else {
                        for ($index = 0; $index <= count($res) - 1; $index++) {
                            if (!empty($res[$index]['title']) && !empty($res[$index]['inverse']) &&
                                (isset($res[$index]['childId']) || isset($res[$index]['childUUID']) ||
                                isset($res[$index]['externalId']))
                            ) {
                                $title = $res[$index]['title'];
                                if (!empty($res[$index]['childId'])) {
                                    $insideUri = $this->oeawFunctions->detailViewUrlDecodeEncode($res[$index]['childId'], 1);
                                } elseif (!empty($res[$index]['childUUID'])) {
                                    $insideUri = $this->oeawFunctions->detailViewUrlDecodeEncode($res[$index]['childUUID'], 1);
                                } elseif (!empty($res[$index]['externalId'])) {
                                    $insideUri = $this->oeawFunctions->detailViewUrlDecodeEncode($res[$index]['externalId'], 1);
                                }
                                $invData["data"][$index] = array($res[$index]['inverse'], "<a href='/browser/oeaw_detail/$insideUri'>$title</a>");
                            }
                        }
                    }
                }
            }
        }
        $response = new Response();
        $response->setContent(json_encode($invData));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    /**
      *
      * This function is for the oeaw_detail view. It is used for the Organisations view, to get the isMembers
      *
      * @param string $data - the resource url
      * @return Response
    */
    public function oeaw_ismember_result(string $data): Response
    {
        $memberData = array();
        
        if (!empty($data)) {
            $identifier = $this->oeawFunctions->detailViewUrlDecodeEncode($data, 0);
            $fdUrlArr = array();
            $fdUrlArr = $this->oeawStorage->getTitleByIdentifier($identifier);
            if (count($fdUrlArr) > 0) {
                if (isset($fdUrlArr[0]['uri'])) {
                    $uri = $fdUrlArr[0]['uri'];
                    $res = $this->oeawStorage->getIsMembers($uri);
                }
             
                if (count($res) <= 0) {
                    $memberData["data"] = array();
                } else {
                    for ($index = 0; $index <= count($res) - 1; $index++) {
                        if (!empty($res[$index]['title']) &&
                                (isset($res[$index]['childId']) || isset($res[$index]['childUUID']) ||
                                isset($res[$index]['externalId']))) {
                            $title = $res[$index]['title'];
                            if (!empty($res[$index]['childId'])) {
                                $insideUri = $this->oeawFunctions->detailViewUrlDecodeEncode($res[$index]['childId'], 1);
                            } elseif (!empty($res[$index]['childUUID'])) {
                                $insideUri = $this->oeawFunctions->detailViewUrlDecodeEncode($res[$index]['childUUID'], 1);
                            } elseif (!empty($res[$index]['externalId'])) {
                                $insideUri = $this->oeawFunctions->detailViewUrlDecodeEncode($res[$index]['externalId'], 1);
                            }
                            
                            $memberData["data"][$index] = array("<a href='/browser/oeaw_detail/$insideUri'>$title</a>");
                        }
                    }
                }
            }
        }

        $response = new Response();
        $response->setContent(json_encode($memberData));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    
    /**
     *
     * This function will download the 3d model with a guzzle async request.
     * After the download it will save the file
     * to the drupal/sites/files/file_name_dir/file_name.extension directory and
     * pass the url to the 3d viewer template
     *
     * @param string $data -> the resource pid or identifier for the 3d content
     * @return array
     */
    public function oeaw_3d_viewer(string $data): array
    {
        if (empty($data)) {
            drupal_set_message(t('No').' '.t('Data'), 'error', false);
            return array();
        }
        $templateData["insideUri"] = $data;
        $identifier = $this->oeawFunctions->detailViewUrlDecodeEncode($data, 0);
        
        $templateData = array();
        //get the title and the fedora url
        $fdUrlArr = array();
        $fdUrlArr = $this->oeawStorage->getTitleByIdentifier($identifier);
        
        if (count($fdUrlArr) > 0) {
            if (isset($fdUrlArr[0]['title'])) {
                $templateData["title"] = $fdUrlArr[0]['title'];
            }
            if (isset($fdUrlArr[0]) && isset($fdUrlArr[0]['uri'])) {
                $fdUrl = $fdUrlArr[0]['uri'];
            } else {
                drupal_set_message(t('The URL %url is not valid.', $fdUrl), 'error', false);
                return array();
            }
        } else {
            drupal_set_message(t('No').' '.t('Data'), 'error', false);
            return array();
        }
        
        //get the filename
        $fdFileName = $this->oeawStorage->getValueByUriProperty($fdUrl, "http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#filename");
        $fdFileSize = $this->oeawStorage->getValueByUriProperty($fdUrl, RC::get('fedoraExtentProp'));
        //if we have a filename in the fedora
        if (isset($fdFileName[0]["value"]) && (count($fdFileName) > 0)) {
            //get the title
            $dir = str_replace(".", "_", $fdFileName[0]["value"]);
            $fileDir = $_SERVER['DOCUMENT_ROOT'].'/sites/default/files/'.$dir.'/'.$fdFileName[0]["value"];
            
            //if the filename is exists then we will not download it again from the server
            if ((file_exists($fileDir)) && (isset($fdFileSize[0]['value']) &&  $fdFileSize[0]['value'] == filesize($fileDir))) {
                $url = '/sites/default/files/'.$dir.'/'.$fdFileName[0]["value"];
                
                $result =  array(
                        '#theme' => 'oeaw_3d_viewer',
                        '#ObjectUrl' => $url,
                        '#templateData' => $templateData,
                    );
                return $result;
            }
        } else {
            drupal_set_message(t('Missing').':'.t('File information'), 'error', false);
            return array();
        }
        
        //this is a new 3d model, so we need to download it to the server.
        $client = new \GuzzleHttp\Client(['auth' => [RC::get('fedoraUser'), RC::get('fedoraPswd')], 'verify' => false]);
        
        try {
            $request = new \GuzzleHttp\Psr7\Request('GET', $fdUrl);
            //send async request
            $promise = $client->sendAsync($request)->then(function ($response) {
                if ($response->getStatusCode() == 200) {
                    //get the filename
                    if (count($response->getHeader('Content-Disposition')) > 0) {
                        $txt = explode(";", $response->getHeader('Content-Disposition')[0]);
                        $filename = "";
                        $extension = "";
                        
                        foreach ($txt as $t) {
                            if (strpos($t, 'filename') !== false) {
                                $filename = str_replace("filename=", "", $t);
                                $filename = str_replace('"', "", $filename);
                                $filename = ltrim($filename);
                                $extension = explode(".", $filename);
                                $extension = end($extension);
                                continue;
                            }
                        }

                        if ($extension == "nxs" || $extension == "ply") {
                            if (!empty($filename)) {
                                $dir = str_replace(".", "_", $filename);
                                $tmpDir = $_SERVER['DOCUMENT_ROOT'].'/sites/default/files/'.$dir.'/';
                                //if the file dir is not exists then we will create it
                                // and we will download the file
                                if (!file_exists($tmpDir) || !file_exists($tmpDir.'/'.$filename)) {
                                    mkdir($tmpDir, 0777);
                                    $file = fopen($tmpDir.'/'.$filename, "w");
                                    fwrite($file, $response->getBody());
                                    fclose($file);
                                } else {
                                    //if the file is not exists
                                    if (!file_exists($tmpDir.'/'.$filename)) {
                                        $file = fopen($tmpDir.'/'.$filename, "w");
                                        fwrite($file, $response->getBody());
                                        fclose($file);
                                    }
                                }
                                $url = '/sites/default/files/'.$dir.'/'.$filename;
                                $this->uriFor3DObj['result'] = $url;
                                $this->uriFor3DObj['error'] = "";
                            }
                        } else {
                            $this->uriFor3DObj['error'] = t('File extension').' '.t('Error');
                            $this->uriFor3DObj['result'] = "";
                        }
                    }
                } else {
                    $this->uriFor3DObj['error'] = t('No files available.');
                    $this->uriFor3DObj['result'] = "";
                }
            });
            $promise->wait();
        } catch (\GuzzleHttp\Exception\ClientException $ex) {
            $this->uriFor3DObj['error'] = $ex->getMessage();
            
            $result =
                array(
                    '#theme' => 'oeaw_3d_viewer',
                    '#errorMSG' =>  $this->uriFor3DObj['error']
                );
        
            return $result;
        }
        $result =
                array(
                    '#theme' => 'oeaw_3d_viewer',
                    '#ObjectUrl' => $this->uriFor3DObj['result'],
                    '#templateData' => $templateData,
                    '#errorMSG' =>  $this->uriFor3DObj['error']
                );
        
        return $result;
    }
    
    /**
     *
     * The view for the collection download with some basic information
     *
     * @param string $uri
     * @return string
     */
    public function oeaw_dl_collection_view(string $uri): array
    {
        $errorMSG = "";
        $resData = array();
        $result = array();
        $resData['dl'] = false;
        $resData['insideUri'] = $uri;
        $encIdentifier = $uri;
        $uri = $this->oeawFunctions->detailViewUrlDecodeEncode($uri, 0);
        
        if (empty($uri)) {
            $errorMSG = "There is no valid URL";
        } else {
            $resData = $this->oeawFunctions->generateCollectionData($uri);
           
            if (count($resData) == 0) {
                drupal_set_message(
                    $this->langConf->get('errmsg_collection_not_exists') ? $this->langConf->get('errmsg_collection_not_exists') : 'The Collection does not exist!',
                    'error',
                    false
                );
                return array();
            }
        }
        $result =
            array(
                '#theme' => 'oeaw_dl_collection_tree',
                '#url' => $encIdentifier,
                '#resourceData' => $resData,
                '#errorMSG' =>  $errorMSG,
                '#attached' => [
                    'library' => [
                        'oeaw/oeaw-DL_collection',
                    ]
                ]
            );
         
        return $result;
    }
      
    /**
     *
     * Displaying the federated login with shibboleth
     *
     * @return array
     */
    public function oeaw_shibboleth_login()
    {
        $result = array();
        $userid = \Drupal::currentUser()->id();
        
        if ((isset($_SERVER['HTTP_EPPN']) && $_SERVER['HTTP_EPPN'] != null)
                && (isset($_SERVER['HTTP_AUTHORIZATION']) && $_SERVER['HTTP_AUTHORIZATION'] != null)
                ) {
            drupal_set_message(t('You already signed in!'), 'status', false);
            return $result;
        } else {
            $result =
                array(
                    '#theme' => 'oeaw_shibboleth_login'
                );
        }
        return $result;
    }
    
    
    /**
     *
     * Displaying the iiif viewer
     *
     * @param string $uri
     * @return array
     */
    public function oeaw_iiif_viewer(string $uri): array
    {
        $resData = array();
        $identifier = "";
        if (empty($uri)) {
            drupal_set_message(
                $this->langConf->get('errmsg_url_not_valid') ? $this->langConf->get('errmsg_url_not_valid') : 'The URL is not valid!',
                'error'
            );
            return array();
        } else {
            $identifier = $this->oeawFunctions->detailViewUrlDecodeEncode($uri, 0);
            if ($identifier) {
                $fdUrl = $this->oeawStorage->getFedoraUrlByIdentifierOrPid($identifier);
                //loris url generating fucntion
                $resData = Helper::generateLorisUrl($fdUrl);
            }
            if (count($resData) == 0) {
                drupal_set_message(
                    $this->langConf->get('errmsg_image_not_valid') ? $this->langConf->get('errmsg_image_not_valid') : 'The Image is not valid!',
                    'error'
                );
                return array();
            }
        }
        
        $result =
            array(
                '#theme' => 'oeaw_iiif_viewer',
                '#url' => $uri,
                '#templateData' => $resData
            );
        return $result;
    }
    
    /**
     *
     * This controller view is for the ajax collection tree view generating
     *
     * @param string $uri
     * @return Response
    */
    public function oeaw_get_collection_data(string $uri) : Response
    {
        if (empty($uri)) {
            $errorMSG = t('Missing').': Identifier';
        } else {
            $resData['insideUri'] = $uri;
            $identifier = $this->oeawFunctions->detailViewUrlDecodeEncode($uri, 0);
            $resData = $this->oeawFunctions->generateCollectionData($identifier);
        }
        
        //setup the the treeview data
        $result = array();
        //add the main Root element
        $resData['binaries'][] = array("uri" => $uri, "uri_dl" => $resData['fedoraUri'], "title" => $resData['title'], "text" => $resData['title'], "filename" => str_replace(" ", "_", $resData['filename']), "rootTitle" => "");
        $result = $this->oeawFunctions->convertToTree($resData['binaries'], "text", "rootTitle");
        
        $response = new Response();
        $response->setContent(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
   
    /**
     *
     * The selected files zip download func
     *
     * @param string $uri
     * @return array
     * @throws \Exception
     */
    public function oeaw_dl_collection(string $uri)
    {
        $result = array();
        $errorMSG = "";
        $GLOBALS['resTmpDir'] = "";
        //the binary files
        $binaries = array();
        $binaries = json_decode($_POST['jsonData'], true);
      
        //the main dir
        $tmpDir = $_SERVER['DOCUMENT_ROOT'].'/sites/default/files/collections/';
        //the collection own dir
        $dateID = date("Ymd_his");
        $tmpDirDate = $tmpDir.$dateID;
        
        //if we have binaries then we continue the process
        if (count($binaries) > 0) {
            //if the main directory is not exists
            if (!file_exists($tmpDir)) {
                mkdir($tmpDir, 0777);
            }
            //if we have the main directory then create the sub
            if (file_exists($tmpDir)) {
                //create the actual dir
                
                if (!file_exists($tmpDirDate)) {
                    mkdir($tmpDirDate, 0777);
                    $GLOBALS['resTmpDir'] = $tmpDirDate;
                }
            }
            
            $client = new \GuzzleHttp\Client(['auth' => [RC::get('fedoraUser'), RC::get('fedoraPswd')], 'verify' => false]);
            ini_set('max_execution_time', 1800);
            foreach ($binaries as $b) {
                try {
                    //if we have filename then save it
                    if (isset($b['filename']) && isset($b['uri'])) {
                        $filename = ltrim($b['filename']);
                        //remove spaces from the filenames
                        $filename = str_replace(' ', "_", $filename);

                        if (!file_exists($GLOBALS['resTmpDir']) || !file_exists($GLOBALS['resTmpDir'].'/'.$filename)) {
                            if (!file_exists($GLOBALS['resTmpDir'])) {
                                mkdir($GLOBALS['resTmpDir'], 0777);
                            }
                            $resource = fopen($GLOBALS['resTmpDir'].'/'.$filename, 'w');
                            $stream = \GuzzleHttp\Psr7\stream_for($resource);
                            $client->request('GET', $b['uri'], ['save_to' => $stream]);
                            chmod($GLOBALS['resTmpDir'].'/'.$filename, 0777);
                        } else {
                            //if the file is not exists
                            if (!file_exists($GLOBALS['resTmpDir'].'/'.$filename)) {
                                $resource = fopen($GLOBALS['resTmpDir'].'/'.$filename, 'w');
                                $stream = \GuzzleHttp\Psr7\stream_for($resource);
                                $client->request('GET', $b['uri'], ['save_to' => $stream]);
                                chmod($GLOBALS['resTmpDir'].'/'.$filename, 0777);
                            }
                        }
                    }
                } catch (\GuzzleHttp\Exception\ClientException $ex) {
                    $errorMSG = t('File').' '.t('Download').' '.t('Error');
                }
            }
        }
        
        //if we have files in the directory
        $dirFiles = scandir($tmpDirDate);
        $hasZip = "";
        
        if (count($dirFiles) > 0) {
            chmod($GLOBALS['resTmpDir'], 0777);
            $archiveFile = $tmpDirDate.'/collection.tar.gz';
            $tar = new \PharData($archiveFile);
            try {
                foreach ($dirFiles as $d) {
                    if ($d == "." || $d == ".." || $d == 'collection.tar') {
                        continue;
                    } else {
                        $tarFilename = $d;
                        //if the filename is bigger than 100chars, then we need
                        //to shrink it
                        if (strlen($d) > 100) {
                            $ext = pathinfo($d, PATHINFO_EXTENSION);
                            $tarFilename = str_replace($ext, '', $d);
                            $tarFilename = substr($tarFilename, 0, 90);
                            $tarFilename = $tarFilename.'.'.$ext;
                        }
                        //we will add the files into the tar,
                        //with a localname to skip the server directory structure
                        $tar->addFile($tmpDirDate.'/'.$d, $tarFilename);
                    }
                }
                $tar->compress(\Phar::NONE);
                unlink($archiveFile);
            } catch (Exception $e) {
                echo "Exception : " . $e;
            }
            //check the new dir that it is still generating the zip file or not
            $newDir = scandir($tmpDirDate);
                    
            $checkDir = true;
            do {
                $checkDir = Helper::checkArrayForValue($newDir, "collection.tar.");
                //delete the files and keep the zip only
                foreach ($dirFiles as $file) {
                    if (is_file($tmpDir.$dateID.'/'.$file)) {
                        unlink($tmpDir.$dateID.'/'.$file);
                    }
                }
                sleep(3);
            } while (false);
            
            $hasTar = RC::get('guiBaseUrl').'/sites/default/files/collections/'.$dateID.'/collection.tar';
        }
        
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($hasTar));
        return $response;
    }
    
    
    
    /***************************** FORM functions!   ***************************************/
    
    /**
     *
     * The deposition agreement form success page with the pdf url
     *
     * @param string $url
     * @return array
     */
    public function oeaw_form_success(string $url)
    {
        if (empty($url)) {
            drupal_set_message(
               $this->langConf->get('errmsg_url_not_valid') ? $this->langConf->get('errmsg_url_not_valid') : 'The URL is not valid!',
               'error'
           );
            return array();
        }
        $uid = \Drupal::currentUser()->id();
        // decode the uri hash
        
        //$url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]/modules/oeaw/src/pdftmp".$url.'.pdf';
        $url = '/sites/default/files/'.$url.'/'.$url.'.pdf';
        $datatable = array(
            '#theme' => 'oeaw_form_resource',
            '#result' => $url,
            '#userid' => $uid,
            '#attached' => [
                'library' => [
                'oeaw/oeaw-styles',
                ]
            ]
        );
        
        return $datatable;
    }
    
    /**
    *
    * The autocomplete function to the edit and new form
    *
    * @param \Drupal\oeaw\Controller\request $request
    * @param string $prop1
    * @param string $fieldName
    * @return JsonResponse
    */
    public function autocomplete(request $request, string $prop1, string $fieldName): JsonResponse
    {
        $matches = array();
        $string = $request->query->get('q');
        $matchClass = [];
        //check the user entered char's
        if (strlen($string) < 3) {
            return new JsonResponse(array());
        }
        
        //f.e.: depositor
        $propUri = base64_decode(strtr($prop1, '-_,', '+/='));

        if (empty($propUri)) {
            return new JsonResponse(array());
        }
        
        $fedora = new Fedora();
        //get the property resources
        $rangeRes = null;
        
        try {
            //get the resource uri based on the propertURI
            //f.e: http://purl.org/dc/terms/contributor and the res uri will be a fedora uri
            $prop = $fedora->getResourceById($propUri);
            //get the property metadata
            $propMeta = $prop->getMetadata();
            // check the range property in the res metadata, we will use this in our next query
            $rangeRes = $propMeta->getResource('http://www.w3.org/2000/01/rdf-schema#range');
        } catch (\RuntimeException $e) {
            return new JsonResponse(array());
        }

        if ($rangeRes === null) {
            return new JsonResponse(array()); // range property is missing - no autocompletion
        }
        
        $matchClass = $this->oeawStorage->checkValueToAutocomplete($string, $rangeRes->getUri());
        
        // if we want additional properties to be searched, we should add them here:
        $match = array(
            'title'  => $fedora->getResourcesByPropertyRegEx('http://purl.org/dc/elements/1.1/title', $string),
            'name'   => $fedora->getResourcesByPropertyRegEx('http://xmlns.com/foaf/0.1/name', $string),
            'acdhId' => $fedora->getResourcesByPropertyRegEx(RC::get('fedoraIdProp'), $string),
        );
        
        $matchValue = array();

        if (count($matchClass) > 0) {
            foreach ($matchClass as $i) {
                $matchValue[] = $i;
            }
        } else {
            return new JsonResponse(array());
        }

        foreach ($match as $i) {
            foreach ($i as $j) {
                $matchValue[]['res'] = $j->getUri();
            }
        }
        
        $mv = $this->oeawFunctions->arrUniqueToMultiArr($matchValue, "res");
        
        foreach ($mv as $i) {
            $acdhId = $fedora->getResourceByUri($i);
            $meta = $acdhId->getMetadata();
            
            $label = empty($meta->label()) ? $acdhId : $meta->label();
            //because of the special characters we need to convert it
            $label = htmlentities($label, ENT_QUOTES, "UTF-8");
                
            $matches[] = ['value' => $i , 'label' => $label];

            if (count($matches) >= 10) {
                break;
            }
        }
        
        $response = new JsonResponse($matches);
        $response->setCharset('utf-8');
        $response->headers->set('charset', 'utf-8');
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
    /**
     *
     * the deposition agreement form
     *
     * @param string $formid
     * @return type
     */
    public function oeaw_depagree_base(string $formid = null)
    {
        return $form = \Drupal::formBuilder()->getForm('Drupal\oeaw\Form\DepAgreeOneForm');
    }
    
    /**
     *
     * User ACL revoke function
     *
     * @param string $uri
     * @param string $user
     * @param Request $request
     */
    public function oeaw_revoke(string $uri, string $user, Request $request): JsonResponse
    {
        drupal_get_messages('error', true);
        $matches = array();
        $response = array();
        
        $fedora = new Fedora();
        $fedora->begin();
        $res = $fedora->getResourceByUri($uri);
        $aclObj = $res->getAcl();
        $aclObj->revoke(\acdhOeaw\fedora\acl\WebAclRule::USER, $user, \acdhOeaw\fedora\acl\WebAclRule::READ);
        $aclObj->revoke(\acdhOeaw\fedora\acl\WebAclRule::USER, $user, \acdhOeaw\fedora\acl\WebAclRule::WRITE);
        $fedora->commit();
        
        $asd = array();
        $asd = $this->oeawFunctions->getRules($uri);
        
//        $this->oeawFunctions->revokeRules($uri, $user);
        
        $matches = array(
            "result" => true,
            "error_msg" => "DONE"
            );
        
        $response = new JsonResponse($matches);
        
        $response->setCharset('utf-8');
        $response->headers->set('charset', 'utf-8');
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
}
