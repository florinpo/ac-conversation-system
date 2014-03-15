<?php

/**
 * This is the model class for table "{{contact_list}}".
 *
 * The followings are the available columns in table '{{contact_list}}':
 * @property string $id
 * @property string $owner_id
 * @property string $contact_id
 */
class ContactList extends CActiveRecord {

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return ContactList the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return '{{contact_list}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('owner_id, contact_id', 'required'),
            array('owner_id, contact_id', 'numerical', 'integerOnly' => true),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, owner_id, contact_id', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        return array(
            'owner' => array(self::BELONGS_TO, 'User', 'owner_id'),
            'contact' => array(self::BELONGS_TO, 'User', 'contact_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'owner_id' => 'Owner',
            'contact_id' => 'Contact',
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

        $criteria->compare('id', $this->id, true);
        $criteria->compare('owner_id', $this->owner_id, true);
        $criteria->compare('contact_id', $this->contact_id, true);

        return new CActiveDataProvider($this, array(
                    'criteria' => $criteria,
                ));
    }

}