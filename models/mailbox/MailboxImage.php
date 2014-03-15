<?php

/**
 * This is the model class for table "{{mailbox_image}}".
 *
 * The followings are the available columns in table '{{mailbox_image}}':
 * @property integer $id
 * @property integer $message_id
 * @property string $name
 * @property string $path
 * @property integer $size
 * @property string $extension
 * @property integer $created_date
 * @property integer $update_date
 * @property string $type
 */
class MailboxImage extends CActiveRecord {

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return MailboxImage the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return '{{mailbox_image}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('message_id, size, created_date, update_date', 'numerical', 'integerOnly' => true),
            array('name, path, extension, type', 'length', 'max' => 255),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, message_id, name, path, size, extension, created_date, update_date, type', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'message' => array(self::BELONGS_TO, 'Message', 'message_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'id' => 'ID',
            'message_id' => 'Message',
            'name' => 'Name',
            'path' => 'Path',
            'size' => 'Size',
            'extension' => 'Extension',
            'created_date' => 'Created Date',
            'update_date' => 'Update Date',
            'type' => 'Type',
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

        $criteria->compare('id', $this->id);
        $criteria->compare('message_id', $this->message_id);
        $criteria->compare('name', $this->name, true);
        $criteria->compare('path', $this->path, true);
        $criteria->compare('size', $this->size);
        $criteria->compare('extension', $this->extension, true);
        $criteria->compare('created_date', $this->created_date);
        $criteria->compare('update_date', $this->update_date);
        $criteria->compare('type', $this->type, true);

        return new CActiveDataProvider($this, array(
                    'criteria' => $criteria,
                ));
    }
    
    protected function afterDelete() {
        parent::afterDelete();
        if ($this->path != null && $this->path != '') {
            $old_path = $this->path;
            //Delete old file Sizes
            $sizes = ImageSize::getMailboxSizes();
            foreach ($sizes as $size) {
                if (file_exists(IMAGES_FOLDER . '/' . $size['id'] . '/' . $old_path))
                    @unlink(IMAGES_FOLDER . '/' . $size['id'] . '/' . $old_path);
            }
        }
    }

}