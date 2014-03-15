<?php

class MessageForm extends CFormModel {

    public $body;
    public $subject;
    public $to;
    public $conversation_id;
    public $uploadimg;
  

    /**
     * Declares the validation rules.
     * The rules state that username and password are required,
     * and password needs to be authenticated.
     */
    public function rules() {

        $purifier = new CHtmlPurifier();
        $purifier->options = array('HTML.Allowed' => '');
        
        return array(
            array('to', 'required'),
            array('subject', 'checkSubject','on' => 'compose'),
            array('body', 'checkBody', 'on'=>'reply'),
            array('body', 'checkBodyCompose', 'on'=>'compose'),
            array('conversation_id', 'required', 'on' => 'reply'),
            array('conversation_id', 'numerical', 'integerOnly' => true, 'on' => 'reply'),
            array('body', 'length', 'max' => 2000),
            array('subject', 'length', 'max'=>100, 'on' => 'compose'),
            array('body', 'filter', 'filter' => array($purifier, 'purify')),
            array('uploadimg', 'file', 'allowEmpty' => true),
        );
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels() {
        return array(
            'body' => t('cms', 'Text'),
            'to' => t('cms', 'To'),
            'subject'=> t('cms', 'Subject')
        );
    }
    
    
    public function checkSubject($attribute, $params) {
        if(!empty($this->to)) {
            //$data = explode(',', $this->$attribute);
            if (empty($this->$attribute)) {
                // Checks for only when there is a value
                $this->addError($attribute,  t('cms', 'Inserisci il oggeto'));
            }
        }
        
    }
    
    public function checkBodyCompose($attribute, $params) {
        if(!empty($this->to) && !empty($this->subject)) {
            //$data = explode(',', $this->$attribute);
            if (empty($this->$attribute)) {
                // Checks for only when there is a value
                $this->addError($attribute,  t('cms', 'Inserisci il messagio'));
            }
        }
        
    }
    
    
    public function checkBody ($attribute, $params) {
        if(!empty($this->to)) {
            //$data = explode(',', $this->$attribute);
            if (empty($this->$attribute)) {
                // Checks for only when there is a value
                $this->addError($attribute,  t('cms', 'Inserisci il messagio'));
            }
        }
        
    }
    
}