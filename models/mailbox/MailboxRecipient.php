<?php

/**
 * This is the model class for table "{{mailbox_recipient}}".
 *
 * The followings are the available columns in table '{{mailbox_recipient}}':
 * @property string $message_id
 * @property string $recipient_id
 * @property integer $recipient_del
 * @property integer $recipient_spam
 */
class MailboxRecipient extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return MailboxRecipient the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return '{{mailbox_recipient}}';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('recipient_del, recipient_spam, recipient_flag, recipient_read', 'numerical', 'integerOnly'=>true),
			array('message_id, recipient_id', 'length', 'max'=>11),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('message_id, recipient_id, recipient_del, recipient_spam, recipient_flag, recipient_read', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}
        
	
	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('message_id',$this->message_id,true);
		$criteria->compare('recipient_id',$this->recipient_id,true);
		$criteria->compare('recipient_del',$this->recipient_del);
		$criteria->compare('recipient_spam',$this->recipient_spam);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}