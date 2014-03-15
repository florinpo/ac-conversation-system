<?php

/**
 * This is the model class for table "{{mailbox_interlocutor}}".
 *
 * The followings are the available columns in table '{{mailbox_interlocutor}}':
 * @property string $conversation_id
 * @property string $interlocutor_id
 * @property integer $interlocutor_del
 * @property integer $interlocutor_arch
 * @property integer $interlocutor_spam
 */
class MailboxInterlocutor extends CActiveRecord {

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return MailboxInterlocutor the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return '{{mailbox_interlocutor}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            //array('conversation_id, interlocutor_id', 'required'),
            //array('interlocutor_del', 'default', 'setOnEmpty'=>true, 'value'=>null),
            //array('interlocutor_del, interlocutor_arch, interlocutor_spam, interlocutor_read, interlocutor_flag', 'numerical', 'integerOnly' => true),
            array('conversation_id, interlocutor_id', 'length', 'max' => 11),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('conversation_id, interlocutor_id, interlocutor_del, interlocutor_arch, interlocutor_spam, interlocutor_read', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'conversation' => array(self::BELONGS_TO, 'Mailbox', 'conversation_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'conversation_id' => 'Conversation',
            'interlocutor_id' => 'Interlocutor',
            'interlocutor_del' => 'Interlocutor Del',
            'interlocutor_arch' => 'Interlocutor Arch',
            'interlocutor_spam' => 'Interlocutor Spam',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search() {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria = new CDbCriteria;

        $criteria->compare('conversation_id', $this->conversation_id, true);
        $criteria->compare('interlocutor_id', $this->interlocutor_id, true);
        $criteria->compare('interlocutor_del', $this->interlocutor_del);
        $criteria->compare('interlocutor_arch', $this->interlocutor_arch);
        $criteria->compare('interlocutor_spam', $this->interlocutor_spam);

        return new CActiveDataProvider($this, array(
                    'criteria' => $criteria,
                ));
    }
    
    
    protected function afterSave() {
        $this->conversation->save(false);
        parent::afterSave();
    }

   

}