<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\oeaw\SearchBoxCache;
use Drupal\oeaw\Model\OeawStorage;
use Drupal\oeaw\OeawFunctions;
use acdhOeaw\util\RepoConfig as RC;

class ComplexSearchForm extends FormBase
{
    private $oeawStorage;
    private $oeawFunctions;
    private $langConf;
    
    /**
     * Set up necessary properties
     */
    public function __construct()
    {
        $this->oeawStorage = new OeawStorage();
        $this->oeawFunctions = new OeawFunctions();
        $this->langConf = $this->config('oeaw.settings');
    }
    
    /**
     * Set up the form id
     * @return string
     */
    public function getFormId()
    {
        return "sks_form";
    }
    
    /**
     * Build form
     * @param array $form
     * @param FormStateInterface $form_state
     * @return array
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $this->langConf->get('my_arche_message');
        /****  THE Search Input field  *****/
        $this->createSearchInput($form);
        
        $cache = new SearchBoxCache();
        
        /****  Type of Entity Box  *****/
        $typeCache = array();
        if ($cache->getCachedData('acdhTypes')) {
            $typeCache = $cache->getCachedData('acdhTypes');
        } else {
            $typeCache = $cache->setCacheData('acdhTypes');
        }
        
        
        if (count($typeCache) > 0) {
            $resData["title"] = $this->langConf->get('gui_type_of_entity') ? $this->langConf->get('gui_type_of_entity') : 'Type of Entity' ;
            $resData["type"] = "searchbox_types";
            $resData["fields"] = $typeCache;
            if (count($resData["fields"]) > 0) {
                $this->createBox($form, $resData);
            }
        }
        
        /****  Entitites by Year  BOX *****/
        $entitiesCache = array();
        if ($cache->getCachedData('entities')) {
            $entitiesCache = $cache->getCachedData('entities');
        } else {
            $entitiesCache = $cache->setCacheData('entities');
        }
        
        if (count($entitiesCache) > 0) {
            $dateData["title"] = $this->langConf->get('gui_entities_by_year') ? $this->langConf->get('gui_entities_by_year') :  'Entities by Year';
            $dateData["type"] = "datebox_years";
            $dateData["fields"] = $entitiesCache;
            
            if (count($dateData["fields"]) > 0) {
                $this->createBox($form, $dateData);
            }
        }
        
        /****  Format  BOX *****/
        
        $formatCache = array();
        if ($cache->getCachedData('formats')) {
            $formatCache = $cache->getCachedData('formats');
        } else {
            $formatCache = $cache->setCacheData('formats');
        }
        
        if (count($formatCache) > 0) {
            $formatData["title"] = $this->t('Format');
            $formatData["type"] = "searchbox_format";
            
            $formatData["fields"] = $frm;

            if (count($formatData["fields"]) > 0) {
                $this->createBox($form, $formatData);
            }
        }
        
        
        /****  Entities By date *****/
        
        $entititesTitle = $this->langConf->get('gui_entities_by_date') ? $this->langConf->get('gui_entities_by_date') :  'Entities by Date';
        $form['datebox']['title'] = [
            '#markup' => '<h3 class="extra-filter-heading date-filter-heading closed">'.$entititesTitle.'</h3>'
        ];
        
        $form['datebox']['date_start_date'] = [
          '#type' => 'textfield',
          '#title' => $this->t('From'),
            '#attributes' => array(
                'class' => array('date-filter start-date-filter'),
                'placeholder' => t('dd/mm/yyyy'),
            )
        ];
        
        $form['datebox']['date_end_date'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Until'),
            '#attributes' => array(
                'class' => array('date-filter end-date-filter'),
                'placeholder' => t('dd/mm/yyyy'),
            )
        ];
        return $form;
    }
    
    /**
     * Create the checkbox templates
     *
     * @param array $form
     * @param array $data
     */
    private function createBox(array &$form, array $data)
    {
        $form['search'][$data["type"]] = array(
            '#type' => 'checkboxes',
            '#title' => $this->t($data["title"]),
            '#attributes' => array(
                'class' => array('checkbox-custom', $data["type"]),
            ),
            '#options' =>
                $data["fields"]
        );
    }
    
    
    /**
     * this function creates the search input field
     *
     * @param array $form
     * @return array
     */
    private function createSearchInput(array &$form)
    {
        $form['metavalue'] = array(
            '#type' => 'textfield',
            '#attributes' => array(
                'class' => array('form-control')
            ),
            #'#required' => TRUE,
        );
        
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->langConf->get('gui_apply_selected_filters') ? $this->langConf->get('gui_apply_selected_filters') : 'Apply the selected search filters',
            '#attributes' => array(
                'class' => array('complexsearch-btn')
            ),
            '#button_type' => 'primary',
        );
    }
    
    /**
     * Validate the form
     *
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $metavalue = $form_state->getValue('metavalue');
        $types = $form_state->getValue('searchbox_types');
        if (count($types) > 0) {
            $types = array_filter($types);
        }
        
        $formats = $form_state->getValue('searchbox_format');
        if (count($formats) > 0) {
            $formats = array_filter($formats);
        }
        
        if ((empty($metavalue)) && (count($types) <= 0)
                &&  (count($formats) <= 0)  && empty($form_state->getValue('date_start_date'))
                && empty($form_state->getValue('date_end_date'))) {
            $form_state->setErrorByName('metavalue', $this->t('Missing').': '.t('Keyword').' '.t('or').' '.t('Type'));
        }
    }
    
    /**
     * Form submit
     *
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $metavalue = $form_state->getValue('metavalue');
        
        $extras = array();
        
        $types = $form_state->getValue('searchbox_types');
        $types = array_filter($types);
        $formats = $form_state->getValue('searchbox_format');
        $formats = array_filter($formats);
        
        $startDate = $form_state->getValue('date_start_date');
        $endDate = $form_state->getValue('date_end_date');
                
        if (count($types) > 0) {
            foreach ($types as $t) {
                $extras["type"][] = strtolower($t);
            }
        }
        
        if (count($formats) > 0) {
            foreach ($formats as $f) {
                $extras["formats"][] = strtolower($f);
            }
        }
        
        if (!empty($startDate) && !empty($endDate)) {
            $startDate = str_replace('/', '-', $startDate);
            $startDate = date("Ymd", strtotime($startDate));
            $endDate = str_replace('/', '-', $endDate);
            $endDate = date("Ymd", strtotime($endDate));
            $extras["start_date"] = $startDate;
            $extras["end_date"] = $endDate;
        }

        $metaVal = $this->oeawFunctions->convertSearchString($metavalue, $extras);
        $metaVal = urlencode($metaVal);
        $form_state->setRedirect('oeaw_complexsearch', ["metavalue" => $metaVal, "limit" => 10,  "page" => 1]);
    }
}
