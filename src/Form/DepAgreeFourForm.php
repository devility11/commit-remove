<?php

namespace Drupal\oeaw\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class DepAgreeFourForm extends DepAgreeBaseForm
{
    public function getFormId()
    {
        return 'depagree_form';
    }
    
    public function buildForm(array $form, FormStateInterface $form_state, $formid = null)
    {
        $form = parent::buildForm($form, $form_state);
        $repoFormID = \Drupal::routeMatch()->getParameter("formid");
        
        $this->checkRepoId($repoFormID);
        
        $form['fields']['creators'] = array(
            '#type' => 'fieldset',
            '#open' => true,
            '#title' => t('Creators'),
            '#prefix' => '<div id="creators-wrapper">',
            '#suffix' => '</div>',
        );

        $max = $form_state->get('fields_count');
        if (is_null($max)) {
            $max = 0;
            $form_state->set('fields_count', $max);
        }

        // Add elements that don't already exist
        for ($i=0; $i<=$max; $i++) {
            if (!isset($form['fields']['creators'][$i])) {
                $form['fields']['creators']['creator_l_name_'.$i] = array(
                    '#type' => 'textfield',
                    '#title' => t('Last Name:'),
                    '#required' => false,
                    '#default_value' => $this->store->get('creator_l_name_'.$i) ? $this->store->get('creator_l_name_'.$i) : '',
                    '#prefix' => t('<h2><b>Creator '.($i+1).' data</b></h2>')
                );
                $form['fields']['creators']['creator_f_name_'.$i] = array(
                    '#type' => 'textfield',
                    '#title' => t('First Name:'),
                    '#required' => false,
                    '#default_value' => $this->store->get('creator_f_name_'.$i) ? $this->store->get('creator_f_name_'.$i) : '',
                );
                $form['fields']['creators']['creator_title_'.$i] = array(
                    '#type' => 'textfield',
                    '#title' => t('Title:')
                );
                $form['fields']['creators']['creator_institution_'.$i] = array(
                    '#type' => 'textfield',
                    '#title' => t('Institution:'),
                    '#default_value' => $this->store->get('creator_institution_'.$i) ? $this->store->get('creator_institution_'.$i) : '',
                );
                $form['fields']['creators']['creator_city_'.$i] = array(
                    '#type' => 'textfield',
                    '#title' => t('City:'),
                    '#default_value' => $this->store->get('creator_city_'.$i) ? $this->store->get('creator_city_'.$i) : '',
                );
                $form['fields']['creators']['creator_address_'.$i] = array(
                    '#type' => 'textfield',
                    '#title' => t('Address:'),
                    '#default_value' => $this->store->get('creator_address_'.$i) ? $this->store->get('creator_address_'.$i) : '',
                );
                $form['fields']['creators']['creator_zipcode_'.$i] = array(
                    '#type' => 'textfield',
                    '#title' => t('Zipcode:'),
                    '#default_value' => $this->store->get('creator_zipcode_'.$i) ? $this->store->get('creator_zipcode_'.$i) : '',
                );
                $form['fields']['creators']['creator_phone_'.$i] = array(
                    '#type' => 'tel',
                    '#title' => t('Phone'),
                    '#default_value' => $this->store->get('creator_phone_'.$i) ? $this->store->get('creator_phone_'.$i) : '',
                );
                $form['fields']['creators']['creator_email_'.$i] = array(
                    '#type' => 'email',
                    '#title' => t('Email'),
                    '#default_value' => $this->store->get('creator_email_'.$i) ? $this->store->get('creator_email_'.$i) : '',
                    '#suffix' => '<hr><br>',
                );
            }
        }

        if ($max < 5) {
            $form['fields']['creators']['add'] = array(
                '#type' => 'submit',
                '#name' => 'addfield',
                '#value' => t('Add more field'),
                '#submit' => array(array($this, 'addfieldsubmit')),
                '#ajax' => array(
                    'callback' => array($this, 'addfieldCallback'),
                    'wrapper' => 'creators-wrapper',
                    'effect' => 'fade',
                ),
            );
        }
        
        $form['creators_title2'] = array(
            '#markup' => '<br><br>',
        );
         
        $form['candidate_confirmation'] = array(
            '#type' => 'radios',
            '#title' => ('I read and agree the ....'),
            '#options' => array(
                'Yes' =>t('Yes'),
                'No' =>t('No')
            ),
        );
        
        $form['fields_count_value'] = array(
            '#type' => 'hidden',
            '#name' => 'fields_count_value',
            '#value' => $form_state->get('fields_count')
        );
        
        $form['actions']['previous'] = array(
            '#type' => 'link',
            '#title' => $this->t('Previous'),
            '#attributes' => array(
                'class' => array('btn'),
                'style' => 'margin:10px; color:white;'
            ),
            '#weight' => 0,
            '#url' => Url::fromRoute('oeaw_depagree_three'),
        );
        
        return $form;
    }
  
    /**
    * Ajax submit to add new field.
    */
    public function addfieldsubmit(array &$form, FormStateInterface &$form_state)
    {
        $max = $form_state->get('fields_count') + 1;
        $form_state->set('fields_count', $max);
        $this->store->set('fields_count_value', $max);
        $form_state->setRebuild(true);
    }

    /**
    * Ajax callback to add new field.
    */
    public function addfieldCallback(array &$form, FormStateInterface &$form_state)
    {
        return $form['fields']['creators'];
    }
  
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // drupal_set_message($this->t('@can_name ,Your application is being submitted!', array('@can_name' => $form_state->getValue('candidate_name'))));
        $form4Val = array();
        //get the class and root values from the form
        $form4Val['candidate_confirmation'] = $form_state->getValue('candidate_confirmation');
        $form4Val['fields_count'] = $form_state->get('fields_count');
        $fields_count = $this->store->set('fields_count_value', $form_state->get('fields_count'));
        
        $fc = $form_state->get('fields_count');
        
        for ($i=0; $i <= $fc; $i++) {
            $form4Val['creator_title_'.$i] = $form_state->getValue('creator_title_'.$i);
            $form4Val['creator_l_name_'.$i] = $form_state->getValue('creator_l_name_'.$i);
            $form4Val['creator_f_name_'.$i] = $form_state->getValue('creator_f_name_'.$i);
            $form4Val['creator_institution_'.$i] = $form_state->getValue('creator_institution_'.$i);
            $form4Val['creator_city_'.$i] = $form_state->getValue('creator_city_'.$i);
            $form4Val['creator_address_'.$i] = $form_state->getValue('creator_address_'.$i);
            $form4Val['creator_zipcode_'.$i] = $form_state->getValue('creator_zipcode_'.$i);
            $form4Val['creator_phone_'.$i] = $form_state->getValue('creator_phone_'.$i);
            $form4Val['creator_email_'.$i] = $form_state->getValue('creator_email_'.$i);
        }
        
        $this->store->set('form4Val', $form4Val);
        
        $DBData = array();
    
        //we have record in the DB, so it will be an DB update
        if (isset($this->dbData["data"]) && !empty($this->dbData["data"])) {
            $DBData = json_decode($this->dbData["data"], true);
            //if we dont have a key then we creating one
            if (array_key_exists('four', $DBData) == null) {
                $DBData["four"] = $form4Val;
            } else {
                //if we have it then it will be a modification
                foreach ($DBData as $key => $value) {
                    if ($key == "four") {
                        $DBData["four"] = $form4Val;
                    }
                }
            }
            //we jsut creating the json encode data
            $jsonObj = json_encode($DBData);

            $num_updated = db_update('oeaw_forms')
                ->fields(array(
                        'data' => $jsonObj,
                        'date'=>  date("d-m-Y H:i:s")
                ))
                ->condition('userid', \Drupal::currentUser()->id(), '=')
                ->condition('repoid', $this->repoid, '=')
                ->condition('status', "open", '=')
                ->execute();
        } else {
            $DBData["four"] = $form4Val;
            $jsonObj = json_encode($DBData);
            //this will be a new DB insert
            $field = array(
                'userid' => \Drupal::currentUser()->id(),
                'repoid' => $this->repoid,
                'data' => $jsonObj,
                'status' =>  'open',
                'date'=>  date("d-m-Y H:i:s")
            );
            db_insert('oeaw_forms')
                ->fields($field)
                ->execute();
        }

        
        parent::saveData();
    }
}
