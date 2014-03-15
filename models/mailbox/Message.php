<?php

class Message extends CActiveRecord {

    public $modified;
    public $subject;
    public $is_replied;
    public $initiator_id;
    public $bm_read;
    public $recipientIds;
    public $recipient_flag;
    public $mailboxRecipient;

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Message the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return '{{mailbox_message}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        return array(
            array('text', 'required'),
            array('created, sender_id', 'length', 'max' => 10),
            array('sender_del, sender_spam, sender_flag, sender_read', 'numerical', 'integerOnly' => true),
            array('recipientIds', 'safe'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('message_id, conversation_id, created, sender_id, recipient_id, text, hash', 'safe', 'on' => 'search'),
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
            'recipients' => array(self::MANY_MANY, 'User', 'gxc_mailbox_recipient(message_id, recipient_id)'),
            'images' => array(self::HAS_MANY, 'MailboxImage', 'message_id')
        );
    }

   
     public function behaviors() {
        return array(
            'CAdvancedArBehavior' => array(
                'class' => 'cms.extensions.behaviors.CAdvancedArBehavior',
            ),
             'LastModificationBehavior' => array(
                'class' => 'cms.extensions.behaviors.LastModificationBehavior',
            ),
            
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'message_id' => 'Message',
            'conversation_id' => 'Conversation Id',
            'created' => 'Time Stamp',
            'sender_id' => 'Sender',
            'recipient_id' => 'Recipient',
            'text' => 'Text',
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

        $criteria->compare('message_id', $this->message_id, true);
        $criteria->compare('conversation_id', $this->conversation_id, true);
        $criteria->compare('created', $this->created, true);
        $criteria->compare('sender_id', $this->sender_id, true);
        $criteria->compare('recipient_id', $this->recipient_id, true);
        $criteria->compare('text', $this->text, true);

        return new CActiveDataProvider($this, array(
                    'criteria' => $criteria,
                ));
    }

    /*
     * retrieves the conversation based on its id
     */
    public function conversation($conversation_id) {
        if (!preg_match('/^[0-9]+$/', $conversation_id))
            die('Conversation Id must be an integer:' . $conversation_id);
        $this->getDbCriteria()->mergeWith(array(
            'select' => '*',
            'condition' => 'conversation_id = :cid',
            'params' => array(':cid' => $conversation_id)
        ));
        return $this;
    }

    /*
     * retrieves the messages from trash folder
     */
    public function trash($userid, $order = 'DESC') {
        $this->getDbCriteria()->mergeWith(array(
            'select' => 'm.message_id, m.sender_id, m.conversation_id,
			m.text, m.created, m.sender_del, m.sender_read,
                        m.sender_flag, mr.recipient_flag as recipient_flag, 
                        mr.recipient_read as recipient_read,
			ms.created as is_replied',
            'alias' => 'm',
            'join' => " INNER JOIN {{mailbox_conversation}} AS c ON(c.conversation_id=m.conversation_id) 
                        INNER JOIN {{mailbox_interlocutor}} as i ON (i.conversation_id=c.conversation_id) 
			LEFT JOIN (
				SELECT conversation_id, created FROM {{mailbox_message}} 
			) AS ms ON(ms.conversation_id=m.conversation_id AND ms.created > m.created) 
                        INNER JOIN (
                                SELECT * FROM {{mailbox_recipient}} 
                                ORDER BY message_id DESC 
                       ) as mr ON (mr.message_id=m.message_id)
			",
            'condition' => '(c.initiator_id=:userid AND IF(c.initiator_del>0,1,((m.sender_id=:userid AND m.sender_del>0) OR (mr.recipient_id=:userid AND mr.recipient_del>0))) OR 
                            i.interlocutor_id=:userid AND IF(i.interlocutor_del>0,1,((m.sender_id=:userid AND m.sender_del>0) OR (mr.recipient_id=:userid AND mr.recipient_del>0)))) AND
                            ((m.sender_id=:userid AND m.sender_spam=0) OR (mr.recipient_id=:userid AND mr.recipient_spam=0))',
            'order' => "m.created " . $order,
            'params' => array(':userid' => $userid),
        ));
        return $this;
    }
    
    /*
     * retrieves the messages from sent folder
     */

    public function sent($userid, $order = 'DESC') {
        $this->getDbCriteria()->mergeWith(array(
            'select' => 'm.message_id, m.sender_id, m.conversation_id,
			m.text, m.created, m.sender_del, m.sender_read,
                        m.sender_flag, mr.recipient_flag as recipient_flag,
                         mr.recipient_read as recipient_read,
                        mr.recipient_id as recipient, 
                        ms.created as is_replied',
            'alias' => 'm',
            'join' => " INNER JOIN {{mailbox_conversation}}  AS c ON(c.conversation_id=m.conversation_id) 
                        INNER JOIN {{mailbox_interlocutor}} as i ON (i.conversation_id=c.conversation_id) 
			LEFT JOIN (
				SELECT conversation_id, created FROM {{mailbox_message}} 
			) AS ms ON(ms.conversation_id=m.conversation_id AND ms.created > m.created) 
                        LEFT JOIN (
                                SELECT * FROM {{mailbox_recipient}} 
                                ORDER BY message_id DESC 
                       ) as mr ON (mr.message_id=m.message_id)
			",
            'condition' => 'c.initiator_id=:userid AND ((m.sender_id=:userid AND m.sender_del=0 AND m.sender_spam=0) OR (mr.recipient_id=:userid AND mr.recipient_del=0 AND mr.recipient_spam=0)) OR 
                            i.interlocutor_id=:userid AND ((m.sender_id=:userid AND m.sender_del=0 AND m.sender_spam=0) OR (mr.recipient_id=:userid AND mr.recipient_del=0 AND mr.recipient_spam=0))',
            'order' => "m.created " . $order,
            'params' => array(':userid' => $userid),
        ));
        return $this;
    }
    
    /*
     * retrieves the messages from inbox folder
     */

    public function inbox($userid, $order = 'DESC') {
        $this->getDbCriteria()->mergeWith(array(
            'select' => 'm.message_id, m.sender_id, 
			m.text, m.created, m.sender_del, m.sender_read,
                        m.sender_flag, mr.recipient_flag as recipient_flag,
                         mr.recipient_read as recipient_read,
                        mr.recipient_id as recipient, 
                        ms.created as is_replied',
            'alias' => 'm',
            'join' => " INNER JOIN {{mailbox_conversation}}  AS c ON(c.conversation_id=m.conversation_id) 
                        INNER JOIN {{mailbox_interlocutor}} as i ON (i.conversation_id=c.conversation_id) 
			LEFT JOIN (
				SELECT conversation_id, created FROM {{mailbox_message}} 
			) AS ms ON(ms.conversation_id=m.conversation_id AND ms.created > m.created) 
                        LEFT JOIN (
                                SELECT * FROM {{mailbox_recipient}} 
                                ORDER BY message_id DESC 
                       ) as mr ON (mr.message_id=m.message_id)
			",
            'condition' => 'c.initiator_id=:userid AND ((m.sender_id=:userid AND m.sender_del=0 AND m.sender_spam=0) OR (mr.recipient_id=:userid AND mr.recipient_del=0 AND mr.recipient_spam=0)) OR 
                            i.interlocutor_id=:userid AND ((m.sender_id=:userid AND m.sender_del=0 AND m.sender_spam=0) OR (mr.recipient_id=:userid AND mr.recipient_del=0 AND mr.recipient_spam=0))',
            'order' => "m.created " . $order,
            'params' => array(':userid' => $userid),
        ));
        return $this;
    }
    
    /*
     * retrieves the messages from archived folder
     */

    public function archived($userid, $order = 'DESC') {
        $this->getDbCriteria()->mergeWith(array(
            'select' => 'm.message_id, m.sender_id,
			m.text, m.created, m.sender_del, m.sender_read,
                        m.sender_flag, mr.recipient_flag as recipient_flag,
                         mr.recipient_read as recipient_read,
                        mr.recipient_id as recipient, 
                        ms.created as is_replied',
            'alias' => 'm',
            'join' => " INNER JOIN {{mailbox_conversation}}  AS c ON(c.conversation_id=m.conversation_id) 
                        INNER JOIN {{mailbox_interlocutor}} as i ON (i.conversation_id=c.conversation_id) 
			LEFT JOIN (
				SELECT conversation_id, created FROM {{mailbox_message}} 
			) AS ms ON(ms.conversation_id=m.conversation_id AND ms.created > m.created) 
                        LEFT JOIN (
                                SELECT * FROM {{mailbox_recipient}} 
                                ORDER BY message_id DESC 
                       ) as mr ON (mr.message_id=m.message_id)
			",
            'condition' => 'c.initiator_arch>0 AND c.initiator_id=:userid AND ((m.sender_id=:userid AND m.sender_del=0 AND m.sender_spam=0) OR (mr.recipient_id=:userid AND mr.recipient_del=0 AND mr.recipient_spam=0)) OR 
                            i.interlocutor_arch>0 AND i.interlocutor_id=:userid AND ((m.sender_id=:userid AND m.sender_del=0 AND m.sender_spam=0) OR (mr.recipient_id=:userid AND mr.recipient_del=0 AND mr.recipient_spam=0))',
            'order' => "m.created " . $order,
            'params' => array(':userid' => $userid),
        ));
        return $this;
    }
    
    /*
     * retrieves the messages from spam folder
     */

    public function spam($userid, $order = 'DESC') {
        $this->getDbCriteria()->mergeWith(array(
            'select' => 'm.message_id, m.sender_id,
			m.text, m.created, m.sender_del, m.sender_read,
                        m.sender_flag, mr.recipient_flag as recipient_flag,
                         mr.recipient_read as recipient_read,
                        mr.recipient_id as recipient, 
                        ms.created as is_replied',
            'alias' => 'm',
            'join' => " INNER JOIN {{mailbox_conversation}}  AS c ON(c.conversation_id=m.conversation_id) 
                        INNER JOIN {{mailbox_interlocutor}} as i ON (i.conversation_id=c.conversation_id) 
			LEFT JOIN (
				SELECT conversation_id, created FROM {{mailbox_message}} 
			) AS ms ON(ms.conversation_id=m.conversation_id AND ms.created > m.created) 
                        INNER JOIN (
                                SELECT * FROM {{mailbox_recipient}} 
                                ORDER BY message_id DESC 
                       ) as mr ON (mr.message_id=m.message_id)
			",
            'condition' => 'c.initiator_id=:userid AND ((m.sender_id=:userid AND m.sender_del=0 AND m.sender_spam>0) OR (mr.recipient_id=:userid AND mr.recipient_del=0 AND mr.recipient_spam>0)) OR 
                            i.interlocutor_id=:userid AND ((m.sender_id=:userid AND m.sender_del=0 AND m.sender_spam>0) OR (mr.recipient_id=:userid AND mr.recipient_del=0 AND mr.recipient_spam>0))',
            'order' => "m.created " . $order,
            'params' => array(':userid' => $userid),
        ));
        return $this;
    }
    
    /*
     * check if the logged in user is the sender or the recipient of the message
     * string $userid - current logged in user id
     */
    public function belongsTo($userid) {
        return ($this->sender_id == $userid || in_array($userid, $this->recipientIds));
    }

    /*
     * return the recipient of the message id
     * string $userid - current logged in user id
     */
    public function mailboxRecipient($userid) {
        $recipient = MailboxRecipient::model()->find(array(
            'condition' => 'message_id=:messageId AND recipient_id=:userId',
            'params' => array(':messageId' => $this->message_id, ':userId' => $userid)
                ));
        return $recipient;
    }

    /*
     * return an array of the participants ids of the message
     */
    public function participants() {
        $ids = array();
        $ids[] = $this->sender_id;

        foreach ($this->recipientIds as $id) {
            $ids[] = $id;
        }
        return $ids;
    }

    /*
     * return the ids of senders
     * string $userid - current logged in user id
     * boolean $string - if the output should be an array or a string
     */
    public function sendersIds($userid, $string = false) {
        $ids = array();
        if ($this->sender_id == $userid) {
            $ids = $this->recipientIds;
        } else {
            $ids[] = $this->sender_id;
        }
        if ($string == false)
            return $ids;
        else
            return implode(",", $ids);
    }

    /*
     * return a string of the names of senders comma separated
     * string $userid - current logged in user id
     * boolean $uname - if set to true the output will contain the username otherwise will return only fullname
     * boolean strip - if set to true will strip the html tags
     */
    public function sendersLabels($userid, $uname = true, $strip = true) {
        $ids = $this->sendersIds($userid);
        $labels = array();
        foreach ($ids as $id) {
            $labels[] = GxcHelpers::getDisplayName($id, $strip, $uname);
        }
        return implode(", ", $labels);
    }

    /*
     * return an array of the message senders
     * string $userid - current logged in user id
     * boolean $uname - if set to true the output will contain the username otherwise will return only fullname
     */
    public function senders($userid, $uname = true) {
        $ids = $this->sendersIds($userid);
        $senders = array();
        foreach ($ids as $id) {
            $senders[$id] = GxcHelpers::getDisplayName($id, true, $uname);
        }
        return $senders;
    }
    /*
     * return an array of the conversation senders
     * this function is useful when for the reply all functionality
     * string $convId - the conversation id
     * string $userid - current logged in user id
     * string $folder - the current folder
     * boolean $uname - if set to true the output will contain the username otherwise will return only fullname
     */
    public function replyMultiple($convId, $userid, $folder, $uname = true) {
        $conversationSenders = Mailbox::conversationSenders($convId, $userid, $folder, false, true, $uname);
        $messageSenders = $this->senders($userid, $uname);
        $result = array_diff($conversationSenders, $messageSenders);
        
        if (!empty($result))
            return $conversationSenders;
        else
            return false;
    }
    
    /*
     * check if the message has flag
     */
    public function isFlagged($userid) {
        $recipient = $this->mailboxRecipient($userid);
        if ($this->sender_id == $userid)
            $flag = $this->sender_flag & Mailbox::INITIATOR_FLAG;
        elseif ($recipient != null)
            $flag = $recipient->recipient_flag & Mailbox::INTERLOCUTOR_FLAG;

        return ($flag > 0);
    }
    
   /*
    * check if the messae is unread
    */
    public function isUnread($userid) {
        $recipient = $this->mailboxRecipient($userid);
        if ($this->sender_id == $userid)
            $flag = $this->sender_read;
        elseif ($recipient != null)
            $flag = $recipient->recipient_read;

        return ($flag == 0);
    }

    /*
     * check if the user is already in contact list
     */
    public function isContact($userid) {
        $user = User::model()->findByPk($userid);
        $contacts = $user->contactIds;
        if ($contacts)
            return ($this->sender_id != $userid && (in_array($this->sender_id, $contacts)));
    }
    
    /*
     * check if the message is permanently deleted
     */
    public function isPermDeleted($userid) {
        $recipient = $this->mailboxRecipient($userid);
        if ($this->sender_id == $userid) {
            $flag = $this->sender_del;
        } elseif ($recipient != null) {
            $flag = $recipient->recipient_del;
        }

        return is_null($flag) ? true : false;
    }

    /*=== Functions related for controller actions ===*/
    
    public function restore($userid = 0) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));

        $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior

        if ($this->sender_id == $userid && !is_null($this->sender_del)) {
            $this->sender_del = 0;
            $this->sender_spam = 0;
            $this->save();
        } else if (in_array($userid, $this->recipientIds)) {
            $recipient = $this->mailboxRecipient($userid);
            if (!is_null($recipient->recipient_del)) {
                $recipient->recipient_spam = 0;
                $recipient->recipient_del = 0;
                $recipient->save();
            }
        }
        else
            throw new CHttpException(400, t('cms', 'User denied'));
    }

    public function flag($userid, $folder) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));
        $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior
        if ($this->sender_id == $userid) {
            $this->sender_flag = Mailbox::INITIATOR_FLAG;
            $this->save();
        } else if (in_array($userid, $this->recipientIds)) {
            $recipient = $this->mailboxRecipient($userid);
            $recipient->recipient_flag = Mailbox::INTERLOCUTOR_FLAG;
            $recipient->save();
        }

        return true;
    }

    public function unflag($userid, $folder) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));
        $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior
        if ($this->sender_id == $userid) {
            $this->sender_flag = 0;
            $this->save();
        } else if (in_array($userid, $this->recipientIds)) {
            $recipient = $this->mailboxRecipient($userid);
            $recipient->recipient_flag = 0;
            $recipient->save();
        }

        return true;
    }

    public function read($userid) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));
        else {
            $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior

            if ($this->sender_id == $userid) {
                $this->sender_read = Mailbox::INITIATOR_FLAG;
                $this->save();
            } else if (in_array($userid, $this->recipientIds)) {
                $recipient = $this->mailboxRecipient($userid);
                $recipient->recipient_read = Mailbox::INTERLOCUTOR_FLAG;
                $recipient->save();
            }

            return true;
        }
    }

    public function unread($userid) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));

        else {

            $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior

            if ($this->sender_id == $userid) {
                $this->sender_read = 0;
                $this->save();
            } else if (in_array($userid, $this->recipientIds)) {
                $recipient = $this->mailboxRecipient($userid);
                $recipient->recipient_read = 0;
                $recipient->save();
            }

            return true;
        }
    }

    public function markSpam($userid) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));
        else {
            $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior

            if ($this->sender_id == $userid) {
                $this->sender_spam = Mailbox::INITIATOR_FLAG;
                $this->sender_del = 0;

                $this->save();
            } else if (in_array($userid, $this->recipientIds)) {
                $recipient = $this->mailboxRecipient($userid);
                $recipient->recipient_spam = Mailbox::INTERLOCUTOR_FLAG;
                $recipient->recipient_del = 0;

                $recipient->save();
            }
            return true;
        }
    }

    public function unmarkSpam($userid) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));
        else {
            $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior

            if ($this->sender_id == $userid) {
                $this->sender_spam = 0;
                $this->save();
            } else if (in_array($userid, $this->recipientIds)) {
                $recipient = $this->mailboxRecipient($userid);
                $recipient->recipient_spam = 0;
                $recipient->save();
            }
            return true;
        }
    }

    public function delete($userid, $folder) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));

        $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior

        if ($this->sender_id == $userid) {
            $this->sender_del = Mailbox::INITIATOR_FLAG;
            $this->sender_spam = 0;
            $this->save();
        } else if (in_array($userid, $this->recipientIds)) {
            $recipient = $this->mailboxRecipient($userid);
            $recipient->recipient_del = Mailbox::INTERLOCUTOR_FLAG;
            $recipient->recipient_spam = 0;
            $recipient->save();
        }


        return true;
    }

    public function permanentDelete($userid) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));

        $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior

        if ($this->sender_id == $userid) {
            $this->sender_del = null;
            $this->sender_spam = 0;
            $this->save();
        } else if (in_array($userid, $this->recipientIds)) {
            $recipient = $this->mailboxRecipient($userid);
            $recipient->recipient_del = null;
            $recipient->recipient_spam = 0;
            $recipient->save();
        }
        else
            throw new CHttpException(400, t('cms', 'User denied'));


        $participants = $this->participants();

        $count = 0;

        foreach ($participants as $id) {
            if ($this->isPermDeleted($id))
                $count++;
        }
        if (count($participants) == $count) {
            $this->destroy();
        }

        return true;
    }

    public function destroy() {
        MailboxRecipient::model()->deleteAll('message_id=:messageId', array(':messageId' => $this->message_id));
        return parent::delete();
    }

    public function afterFind() {
        if (!empty($this->recipients)) {
            foreach ($this->recipients as $n => $recipient)
                $this->recipientIds[] = $recipient->user_id;
        }

        parent::afterFind();
    }

    protected function afterSave() {
        if (($this->isNewRecord)){
            $this->addImages();
        }
        parent::afterSave();
    }
    
    protected function afterDelete() {
        parent::afterDelete();
        MailboxImage::model()->deleteAll('message_id = :messageId', array(':messageId' => $this->message_id));
    }

    /*
     *  Upload images
     */
    public function addImages() {
        //If we have p=ending images
        if (Yii::app()->user->hasState('imagesMailbox')) {
            $userImages = Yii::app()->user->getState('imagesMailbox');
            $folder = 'mailbox';

            //Now lets create the corresponding models and move the files

            foreach ($userImages as $image) {

                if (is_file($image["path"])) {
                    $img = new MailboxImage;
                    $img->name = $image["filename"];
                    $img->path = $folder . '/' . $image["filename"];
                    $img->size = $image["size"];
                    $img->type = $image["mime"];
                    $img->extension = $image["extension"];
                    $img->message_id = $this->message_id;
                    $img->created_date = $img->update_date = time();
                    //$img->save();
                    if (!$img->save()) {
                        //Its always good to log something
                        Yii::log("Could not save Image:\n" . CVarDumper::dumpAsString(
                                        $img->getErrors()), CLogger::LEVEL_ERROR);
                        //this exception will rollback the transaction
                        throw new Exception('Could not save Image');
                    }

                    @unlink($image["path"]);
                }

                if (is_file($image["100"])) {
                    $path = IMAGES_FOLDER . DIRECTORY_SEPARATOR . 'img100' . DIRECTORY_SEPARATOR . $folder;


                    if (!(file_exists($path) && ($path))) {
                        mkdir($path, 0777, true);
                    }

                    if (!(file_exists($path . DIRECTORY_SEPARATOR . 'index.html'))) {
                        $fp = fopen($path . DIRECTORY_SEPARATOR . 'index.html', 'w'); // open in write mode.
                        fclose($fp); // close the file.
                    }

                    if (rename($image["100"], $path . DIRECTORY_SEPARATOR . $image["filename"])) {
                        chmod($path . DIRECTORY_SEPARATOR . $image["filename"], 0777);
                    }
                }
                if (is_file($image["400"])) {

                    $path = IMAGES_FOLDER . DIRECTORY_SEPARATOR . 'img400' . DIRECTORY_SEPARATOR . $folder;


                    if (!(file_exists($path) && ($path))) {
                        mkdir($path, 0777, true);
                    }

                    if (!(file_exists($path . DIRECTORY_SEPARATOR . 'index.html'))) {
                        $fp = fopen($path . DIRECTORY_SEPARATOR . 'index.html', 'w'); // open in write mode.
                        fclose($fp); // close the file.
                    }

                    if (rename($image["400"], $path . DIRECTORY_SEPARATOR . $image["filename"])) {
                        chmod($path . DIRECTORY_SEPARATOR . $image["filename"], 0777);
                    }
                }
            }
            Yii::app()->user->setState('imagesMailbox', null);
        }
    }

}