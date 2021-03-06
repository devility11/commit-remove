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
use Drupal\oeaw\Model\OeawStorage;
use Drupal\oeaw\OeawFunctions;

class SidebarDateForm extends FormBase
{
    private $oeawStorage;
    private $oeawFunctions;
    
    public function __construct()
    {
        $this->oeawStorage = new OeawStorage();
        $this->oeawFunctions = new OeawFunctions();
    }
    /**
     * the form id init
     *
     * @return string
     */
    public function getFormId()
    {
        return "sdate_form";
    }
    
    /**
     * form build
     *
     * @param array $form
     * @param FormStateInterface $form_state
     * @return string
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        echo "sidebar date";
        $form['pickup_date'] = array(
                '#type' => 'date',
                '#prefix' => '<div class="container-inline">',
                '#suffix' => '</div>',
        );
        return $form;
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
        //$metavalue = base64_encode($metavalue);
        $metavalue = urlencode($metavalue);
        $form_state->setRedirect('oeaw_keywordsearch', ["metavalue" => $metavalue]);
    }
}
