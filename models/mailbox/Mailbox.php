<?php

class Mailbox extends CActiveRecord {

    const INITIATOR_FLAG = 1;
    const INTERLOCUTOR_FLAG = 2;

 
    public $text;
    public $is_replied;
    public $interlocutorIds;

    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Mailbox the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return '{{mailbox_conversation}}';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            //array('initiator_del,interlocutor_del', 'default', 'setOnEmpty'=>true, 'value'=>null ),
            array('initiator_id, modified', 'required'),
            array('initiator_id, bm_read, bm_archived, bm_spammed, bm_deleted,
                   initiator_arch, initiator_spam, initiator_del, initiator_restored, initiator_read, initiator_flag', 'numerical', 'integerOnly' => true),
            array('subject', 'length', 'max' => 256),
            array('modified', 'length', 'max' => 10),
            array('is_system', 'length', 'max' => 3),
            array('interlocutorIds', 'safe'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('conversation_id, subject, initiator_id, bm_read, bm_archived, bm_spammed, bm_deleted,
                initiator_arch, initiator_spam, initiator_del, initiator_restored, modified, is_system', 'safe', 'on' => 'search'),
                //array('to', 'length', 'max' => 30),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'initiator' => array(self::BELONGS_TO, 'User', 'initiator_id'),
            'interlocutors' => array(self::MANY_MANY, 'User', 'gxc_mailbox_interlocutor(conversation_id, interlocutor_id)'),
            'messages' => array(self::HAS_MANY, 'Message', 'conversation_id'),
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
            'conversation_id' => 'Id',
            'initiator_id' => 'From Id',
            //'to' => 'To',
            'subject' => 'Subject',
            'last_message_ts' => 'Last Message Received',
            'modified' => 'Last Modified',
            'is_system' => 'Is System',
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
        $criteria->compare('initiator_id', $this->initiator_id);
        $criteria->compare('subject', $this->subject, true);
        $criteria->compare('bm_read', $this->bm_read);
        $criteria->compare('bm_deleted', $this->bm_deleted);
        $criteria->compare('bm_archived', $this->bm_archived);
        $criteria->compare('bm_spammed', $this->bm_spammed);
        $criteria->compare('modified', $this->modified, true);
        $criteria->compare('is_system', $this->is_system, true);

        return new CActiveDataProvider($this, array(
                    'criteria' => $criteria,
                ));
    }

    /*
     * Retrieves the conversations from the spam folder
     * var $userid is the authenticated user id Ex: app()->user()->id
     */

    public function spammed($userid) {
        $this->getDbCriteria()->mergeWith(array(
            'select' => 'm.message_id, m.conversation_id as conversation, 
			COUNT(m.message_id) AS mails_count, m.sender_id, 
			m.text, m.created AS last_message_ts, ms.is_replied, m.recipient_id, m.recipient_spam, m.sender_spam,
                        m.sender_read, m.recipient_read,
			c.conversation_id, c.initiator_id, c.subject, c.initiator_read, 
                        c.initiator_del, c.initiator_spam,
			c.bm_deleted, c.bm_spammed, c.bm_archived, c.modified, c.is_system,
                        i.interlocutor_id, i.interlocutor_del, i.interlocutor_arch, 
                        i.interlocutor_spam, i.interlocutor_read',
            'alias' => 'c',
            'join' => "INNER JOIN (
                              SELECT * FROM {{mailbox_interlocutor}}
                       ) as i ON (i.conversation_id=c.conversation_id)  
                       INNER JOIN (
				SELECT m.message_id,conversation_id,sender_id,sender_del,sender_spam, sender_read, text,created,
                                mr.recipient_id, mr.recipient_del, mr.recipient_spam, mr.recipient_read 
                                FROM {{mailbox_message}} as m INNER JOIN {{mailbox_recipient}} as mr 
                                ON (mr.message_id=m.message_id) 
                                WHERE (sender_id=:userid AND sender_spam>0 AND sender_del=0) OR 
                                (mr.recipient_id=:userid AND mr.recipient_spam>0 AND mr.recipient_del=0)
                                ORDER BY created DESC 
		       ) AS m ON(m.conversation_id=c.conversation_id)
                       INNER JOIN (
				SELECT conversation_id, IF(sender_id=:userid,1,0) AS is_replied FROM {{mailbox_message}} 
				ORDER BY created DESC 
		       ) AS ms ON(ms.conversation_id=c.conversation_id)
                       ",
            'condition' => '((c.initiator_id=:userid AND c.initiator_del=0) OR 
                            (i.interlocutor_id=:userid AND i.interlocutor_del=0))',
            'group' => "c.conversation_id",
            'order' => "MAX( m.created ) DESC",
            'params' => array(
                ':userid' => $userid
            )
        ));
        return $this;
    }

    /*
     * Retrieves the conversations from the archived folder
     * var $userid is the authenticated user id Ex: app()->user()->id
     */

    public function archived($userid) {
        $this->getDbCriteria()->mergeWith(array(
            'select' => 'm.message_id, m.conversation_id as conversation, 
			COUNT(m.message_id) AS mails_count, m.sender_id, 
			m.text, m.created AS last_message_ts, ms.is_replied, m.recipient_id, m.recipient_spam, m.sender_spam,
                        m.sender_flag, m.recipient_flag, m.sender_read, m.recipient_read, 
			c.conversation_id, c.initiator_id, c.subject, c.initiator_read, 
                        c.initiator_del, c.initiator_spam,
			c.bm_deleted, c.bm_spammed, c.bm_archived, c.modified, c.is_system,
                        i.interlocutor_id, i.interlocutor_del, i.interlocutor_arch, 
                        i.interlocutor_spam, i.interlocutor_read',
            'alias' => 'c',
            'join' => "INNER JOIN (
                              SELECT * FROM {{mailbox_interlocutor}}
                       ) as i ON (i.conversation_id=c.conversation_id)  
                       INNER JOIN (
				SELECT m.message_id, conversation_id,sender_id,sender_del,sender_spam, sender_flag,sender_read, text,created,
                                mr.recipient_id, mr.recipient_del, mr.recipient_spam, mr.recipient_flag, mr.recipient_read 
                                FROM {{mailbox_message}} as m INNER JOIN {{mailbox_recipient}} as mr 
                                ON (mr.message_id=m.message_id) 
                                WHERE (sender_id=:userid AND sender_del=0 AND sender_spam=0) OR 
                                (mr.recipient_id=:userid AND mr.recipient_del=0 AND mr.recipient_spam=0)
                                ORDER BY created DESC
		       ) AS m ON(m.conversation_id=c.conversation_id)
                       INNER JOIN (
				SELECT conversation_id, IF(sender_id=:userid,1,0) AS is_replied FROM {{mailbox_message}} 
				ORDER BY created DESC 
		       ) AS ms ON(ms.conversation_id=c.conversation_id)
                       ",
            'condition' => '((c.initiator_id=:userid AND c.initiator_arch>0) OR 
                            (i.interlocutor_id=:userid AND i.interlocutor_arch>0))',
            'group' => "c.conversation_id",
            'order' => "MAX( m.created ) DESC",
            'params' => array(
                ':userid' => $userid
            )
        ));
        return $this;
    }

    /*
     * Retrieves the conversations from the inbox folder
     * var $userid is the authenticated user id Ex: app()->user()->id
     */

    public function inbox($userid) {
        $this->getDbCriteria()->mergeWith(array(
            'select' => 'm.message_id, m.conversation_id as conversation, 
			COUNT(m.message_id) AS mails_count, m.sender_id, 
			m.text, m.created AS last_message_ts, ms.is_replied, m.recipient_id, m.recipient_spam, m.sender_spam,
                        m.sender_flag, m.recipient_flag, m.sender_read, m.recipient_read, 
			c.conversation_id, c.initiator_id, c.subject, c.initiator_read, 
                        c.initiator_del, c.initiator_spam,
			c.bm_deleted, c.bm_spammed, c.bm_archived, c.modified, c.is_system,
                        i.interlocutor_id, i.interlocutor_del, i.interlocutor_arch, 
                        i.interlocutor_spam, i.interlocutor_read',
            'alias' => 'c',
            'join' => "INNER JOIN (
                              SELECT * FROM {{mailbox_interlocutor}}
                       ) as i ON (i.conversation_id=c.conversation_id) 
                       INNER JOIN (
				SELECT m.message_id, conversation_id,sender_id,sender_del,sender_spam, sender_flag,sender_read, text,created,
                                mr.recipient_id, mr.recipient_del, mr.recipient_spam, mr.recipient_flag, mr.recipient_read   
                                FROM {{mailbox_message}} as m INNER JOIN {{mailbox_recipient}} as mr 
                                ON (mr.message_id=m.message_id) 
                                WHERE (sender_id=:userid AND sender_del=0 AND sender_spam=0) OR 
                                (mr.recipient_id=:userid AND mr.recipient_del=0 AND mr.recipient_spam=0)
                                ORDER BY created DESC
		       ) AS m ON(m.conversation_id=c.conversation_id)
                       INNER JOIN (
				SELECT conversation_id, IF(sender_id=:userid,1,0) AS is_replied FROM {{mailbox_message}} 
				ORDER BY created DESC 
		       ) AS ms ON(ms.conversation_id=c.conversation_id)
                       ",
            'condition' => '((c.initiator_id=:userid AND c.initiator_del=0 AND c.initiator_arch=0 AND c.initiator_restored>0) OR 
                            (i.interlocutor_id=:userid AND i.interlocutor_del=0 AND i.interlocutor_arch=0))',
            'group' => "c.conversation_id",
            'order' => "MAX( m.created ) DESC",
            'params' => array(
                ':userid' => $userid
            )
        ));
        return $this;
    }

    /*
     * Retrieves the conversations from the sent folder
     * var $userid is the authenticated user id Ex: app()->user()->id
     */

    public function sent($userid) {
        $this->getDbCriteria()->mergeWith(array(
            'select' => 'm.message_id, m.conversation_id as conversation, 
			COUNT(m.message_id) AS mails_count, m.sender_id, 
			m.text, m.created AS last_message_ts, ms.is_replied, m.recipient_id, m.recipient_spam, m.sender_spam,
                        m.sender_flag, m.recipient_flag, m.sender_read, m.recipient_read, 
			c.conversation_id, c.initiator_id, c.subject, c.initiator_read, 
                        c.initiator_del, c.initiator_spam,
			c.bm_deleted, c.bm_spammed, c.bm_archived, c.modified, c.is_system,
                        i.interlocutor_id, i.interlocutor_del, i.interlocutor_arch, 
                        i.interlocutor_spam, i.interlocutor_read',
            'alias' => 'c',
            'join' => "INNER JOIN (
                              SELECT * FROM {{mailbox_interlocutor}}
                       ) as i ON (i.conversation_id=c.conversation_id) 
                        INNER JOIN (
                                SELECT m.message_id, conversation_id,sender_id,sender_del,sender_spam, sender_flag,sender_read, text,created,
                                mr.recipient_id, mr.recipient_del, mr.recipient_spam, mr.recipient_flag, mr.recipient_read   
                                FROM {{mailbox_message}} as m INNER JOIN {{mailbox_recipient}} as mr 
                                ON (mr.message_id=m.message_id) 
                                WHERE (sender_id=:userid AND sender_del=0 AND sender_spam=0) 
                                ORDER BY created DESC
                        ) AS m ON(m.conversation_id=c.conversation_id) 
                        INNER JOIN (
                                SELECT conversation_id, IF(sender_id=:userid,1,0) AS is_replied FROM {{mailbox_message}} 
                                ORDER BY created DESC 
                        ) AS ms ON(ms.conversation_id=c.conversation_id)
                        ",
            'condition' => '((initiator_id=:userid AND c.initiator_del=0) OR (i.interlocutor_id=:userid AND i.interlocutor_del=0))',
            'group' => "c.conversation_id",
            'order' => "MAX( m.created ) DESC",
            'params' => array(':userid' => $userid)
        ));
        return $this;
    }

    /*
     * Retrieves the conversations from the trash folder
     * var $userid is the authenticated user id Ex: app()->user()->id
     */

    public function trash($userid) {
        $this->getDbCriteria()->mergeWith(array(
            'select' => 'm.message_id, m.conversation_id as conversation, 
			COUNT(m.message_id) AS mails_count, m.sender_id, 
			m.text, m.created AS last_message_ts, ms.is_replied, m.recipient_id, m.recipient_spam, m.sender_spam,
                        m.sender_flag, m.recipient_flag, m.sender_read, m.recipient_read,
			c.conversation_id, c.initiator_id, c.subject, c.initiator_read, 
                        c.initiator_del, c.initiator_spam,
			c.bm_deleted, c.bm_spammed, c.bm_archived, c.modified, c.is_system,
                        i.interlocutor_id, i.interlocutor_del, i.interlocutor_arch, 
                        i.interlocutor_spam, i.interlocutor_read',
            'alias' => 'c',
            'join' => "INNER JOIN (
                              SELECT * FROM {{mailbox_interlocutor}}
                       ) as i ON (i.conversation_id=c.conversation_id) 
                       INNER JOIN (
				SELECT m.message_id, conversation_id,sender_id,sender_del,sender_spam, sender_flag,sender_read, text,created,
                                mr.recipient_id, mr.recipient_del, mr.recipient_spam, mr.recipient_flag, mr.recipient_read 
                                FROM {{mailbox_message}} as m INNER JOIN {{mailbox_recipient}} as mr 
                                ON (mr.message_id=m.message_id) 
                                ORDER BY created DESC
		       ) AS m ON(m.conversation_id=c.conversation_id) 
                       INNER JOIN (
                                SELECT conversation_id, IF(sender_id=:userid,1,0) AS is_replied FROM {{mailbox_message}} 
                                ORDER BY created DESC 
                        ) AS ms ON(ms.conversation_id=c.conversation_id)",
            'condition' => '((c.initiator_id=:userid AND c.initiator_del IS NOT NULL) OR 
                            (i.interlocutor_id=:userid AND i.interlocutor_del IS NOT NULL)) AND 
                            ((m.sender_id=:userid AND m.sender_del>0) OR 
                               (m.recipient_id=:userid AND m.recipient_del>0) OR
                               IF(c.initiator_id=:userid,c.initiator_del,i.interlocutor_del) > 0)',
            'group' => "c.conversation_id",
            "having" => "(m.sender_id=:userid AND m.sender_spam=0) OR (m.recipient_id=:userid AND m.recipient_spam=0)",
            'order' => "MAX( m.created ) DESC",
            'params' => array(':userid' => $userid)
        ));
        return $this;
    }

    /*
     * Counts the unread messages from the current folder
     * var $userid is the authenticated user id Ex: app()->user()->id
     * var $folder is the current folder
     */

    public static function countUnreadMsgs($userid, $folder = 'inbox') {
        // count messages
        if ($folder == 'spam') {
            $query = "SELECT COUNT(c.conversation_id) AS num_messages 
			FROM {{mailbox_conversation}} AS c
                        INNER JOIN (
                              SELECT * FROM {{mailbox_interlocutor}}
                       ) as i ON (i.conversation_id=c.conversation_id) 
			INNER JOIN (
				SELECT m.message_id, conversation_id,sender_id,sender_read,sender_del, sender_spam,
                                mr.recipient_id, mr.recipient_read, mr.recipient_del, mr.recipient_spam  
                                FROM {{mailbox_message}} as m INNER JOIN {{mailbox_recipient}} as mr 
                                ON (mr.message_id=m.message_id AND mr.recipient_read=0) 
                                WHERE (m.sender_id=:userid AND m.sender_spam>0 AND m.sender_del=0 AND m.sender_read=0) OR
                                (mr.recipient_id=:userid AND mr.recipient_spam>0 AND mr.recipient_del=0 AND mr.recipient_read=0)
			) AS m ON(m.conversation_id=c.conversation_id ) 
			WHERE ((c.initiator_id=:userid AND c.initiator_del=0) OR 
                            (i.interlocutor_id=:userid AND i.interlocutor_del=0)) 
			GROUP BY c.conversation_id 
			";
        } else {
            $query = "SELECT COUNT(c.conversation_id) AS num_messages 
			FROM {{mailbox_conversation}} AS c
                        INNER JOIN (
                              SELECT * FROM {{mailbox_interlocutor}}
                       ) as i ON (i.conversation_id=c.conversation_id) 
			INNER JOIN (
				SELECT m.message_id, conversation_id,sender_id,sender_read,sender_del, sender_spam,
                                mr.recipient_id, mr.recipient_read, mr.recipient_del, mr.recipient_spam  
                                FROM {{mailbox_message}} as m INNER JOIN {{mailbox_recipient}} as mr 
                                ON (mr.message_id=m.message_id AND mr.recipient_read=0) 
                                WHERE (m.sender_id=:userid AND m.sender_spam=0 AND m.sender_del=0 AND m.sender_read=0) OR
                                (mr.recipient_id=:userid AND mr.recipient_spam=0 AND mr.recipient_del=0 AND mr.recipient_read=0)
			) AS m ON(m.conversation_id=c.conversation_id ) 
			WHERE ((c.initiator_id=:userid AND c.initiator_del=0 AND c.initiator_arch=0 AND c.initiator_restored>0) OR 
                            (i.interlocutor_id=:userid AND i.interlocutor_del=0 AND i.interlocutor_arch=0)) 
			GROUP BY c.conversation_id 
			";
        }

        $sql = Yii::app()->db->createCommand($query);
        $sql->bindValue(':userid', $userid, PDO::PARAM_STR);


        $convs = $sql->queryAll();
        $count = 0;
        foreach ($convs as &$conv) {
            if ($conv['num_messages'] >= 1)
                $count++;
        }
        return $count;
    }

    /*
     *  retrieves the last message from the current current folder of the current conversation
     */

    public function lastMessage($userid = 0, $folder) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));

        $cMessages = $this->messages(array('scopes' => array($folder => $userid)));

        return $cMessages[0];
    }

    /*
     *  retrieves the last received message from the current current folder of the current conversation
     */

    public function lastReceived($userid = 0, $folder) {

        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));

        $cMessages = $this->messages(array(
            'scopes' => array($folder => $userid),
            'condition' => 'mr.recipient_id=:userid',
            'params' => array(':userid' => $userid)
                ));

        return $cMessages[0];
    }

    /*
     * retrieves the conversation
     */

    public static function conversation($id, $order = 'ASC') {
        if (!is_int((int) $id))
            throw new CHttpException(400, t('cms', 'Invalid request. Please do not repeat this request again.'));
        $with = array('messages' => array('order' => 'created ' . $order));
        return Mailbox::model()->with($with)->findByPk($id);
    }

    /*
     * retrieves the recipients of the conversation
     * string $id - the conversation id
     * string $userid - the id of the authenticated user
     * boolean $strip if the output recipients should contain the html tags or not
     * boolean $uname if the output should contain the username if is false will output the fullname of the recipient
     */

    public static function conversationRecipients($id, $userid, $strip = true, $uname = false) {

        $conv = Mailbox::model()->findByPk($id);
        $messages = $conv->messages(array('scopes' => array('inbox' => $userid)));

        $recipients = array();
        foreach ($messages as $m) {
            if (count($m->recipients) > 0) {
                foreach ($m->recipients as $recipient) {
                    if ($recipient->user_id != $userid) {
                        $recipients[$recipient->user_id] = GxcHelpers::getDisplayName($recipient->user_id, $strip, $uname);
                    }
                }
            }
        }
        return $recipients = array_unique($recipients, SORT_REGULAR);
    }

    /*
     * retrieves the senders of the conversation
     * string $id - the conversation id
     * string $userid - the id of the authenticated user
     * string folder - the current folder
     * boolean include - if set to true and if current logged in user is the sender will add the string "me" instead of full name
     * boolean $strip if the output recipients should contain the html tags or not
     * boolean $uname if the output should contain the username if is false will output the fullname of the recipient
     */
    public static function conversationSenders($id, $userid, $folder = 'inbox', $include = true, $strip = true, $uname = false) {

        $conv = Mailbox::model()->findByPk($id);

        $messages = $conv->messages(array('scopes' => array($folder => $userid)));
        $senders = array();
        foreach ($messages as $m) {
            if ($m->sender_id == $userid) {
                if ($include == true)
                    $senders['r'] = t('site', 'me');
            } else {
                $senders[$m->sender_id] = GxcHelpers::getDisplayName($m->sender_id, $strip, $uname);
            }
        }
        return $senders;
    }

    /*
     * retrieves the interlocutor of the current conversation
     */
    public function mailboxInterlocutor($userid) {
        $mailboxInter = MailboxInterlocutor::model()->find(array(
            'condition' => 'conversation_id=:conversationId AND interlocutor_id=:userId',
            'params' => array(':conversationId' => $this->conversation_id, ':userId' => $userid)
                ));
        return $mailboxInter;
    }

    /*
     *  check if the conversation belongs of the logged in user
     */
    public function belongsTo($userid) {
        return ($this->initiator_id == $userid || in_array($userid, $this->interlocutorIds));
    }

    /*
     * check if the conversation has files attached
     */
    public function hasFile($userid, $folder = 'inbox') {
        $lastMessage = $this->lastMessage($userid, $folder);
        $mailboxInter = $this->mailboxInterlocutor($userid);
        $flag = 0;
        if ($this->initiator_id == $userid)
            $flag = count($lastMessage->images);
        elseif ($mailboxInter != null)
            $flag = count($lastMessage->images);
        return ($flag > 0);
    }

    /*
     * check if the conversation has been marked as spam
     */
    public function isSpam($userid, $folder = 'inbox') {
        $messages = $this->spamMessages($userid, $folder);
        $mailboxInter = $this->mailboxInterlocutor($userid);
        $flag = 0;
        if ($this->initiator_id == $userid)
            $flag = count($messages);
        elseif ($mailboxInter != null)
            $flag = count($messages);
        return ($flag > 0);
    }

    /*
     * check if the conversation has unread messages
     */
    public function isNew($userid, $folder = 'inbox') {
        $messages = $this->unreadMessages($userid, $folder);
        $mailboxInter = $this->mailboxInterlocutor($userid);
        if ($this->initiator_id == $userid)
            $flag = count($messages);
        elseif ($mailboxInter != null)
            $flag = count($messages);
        return ($flag > 0);
    }

    /*
     * check if the conversation is flagged
     */
    public function isFlagged($userid, $folder = 'inbox') {

        $messages = $this->flaggedMessages($userid, $folder);
        $mailboxInter = $this->mailboxInterlocutor($userid);
        if ($this->initiator_id == $userid)
            $flag = count($messages);
        elseif ($mailboxInter != null)
            $flag = count($messages);

        return ($flag > 0);
    }

    /*
     * return the unread messages of the current conversation
     */
    public function unreadMessages($userid = 0, $folder) {

        return $this->messages(array(
                    'scopes' => array($folder => $userid),
                    'condition' => "(sender_id=:userid AND sender_read=0) OR (mr.recipient_id=:userid AND recipient_read=0)",
                    'params' => array(':userid' => $userid)
                ));
    }

    /*
     * return the flagged messages of the current conversation
     */
    public function flaggedMessages($userid = 0, $folder) {

        return $this->messages(array(
                    'scopes' => array($folder => $userid),
                    'condition' => "(sender_id=:userid AND sender_flag>0) OR (mr.recipient_id=:userid AND recipient_flag>0)",
                    'params' => array(':userid' => $userid)
                ));
    }

    /*
     * return the spam messages of the current conversation
     */
    public function spamMessages($userid = 0, $folder) {

        return $this->messages(array(
                    'scopes' => array($folder => $userid),
                    'condition' => "(sender_id=:userid AND sender_spam>0) OR (mr.recipient_id=:userid AND recipient_spam>0)",
                    'params' => array(':userid' => $userid)
                ));
    }

   
    /*=== Functions related for controller actions ===*/

    /*
     * mark as read the conversation
     * string $userid - the logged in user id
     * string $folder - the current folder
     */
    public function read($userid, $folder) {
        if (!$userid) {
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));
        } else {
            $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior


            $messages = $this->messages(array('scopes' => array($folder => $userid)));

            foreach ($messages as $message) {

                $message->read($userid);
            }

            return true;
        }
    }
    
    /*
     * mark as unread the conversation
     * string $userid - the logged in user id
     * string $folder - the current folder
     */

    public function unread($userid, $folder) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));
        $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior

        if ($this->initiator_id == $userid) {
            $message = $this->lastMessage($userid, $folder);
            $message->unread($userid);
        } else if (in_array($userid, $this->interlocutorIds)) {
            $message = $this->lastMessage($userid, $folder);
            $message->unread($userid);
        }
        else
            throw new CHttpException(400, t('cms', 'User denied'));
        return true;
    }

    /*
     * mark as flagged the conversation
     * string $userid - the logged in user id
     * string $folder - the current folder
     */
    
    public function flag($userid, $folder) {
        if (!$userid) {
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));
        } else {
            $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior

            $this->lastMessage($userid, $folder)->flag($userid, $folder);

            return true;
        }
    }
    
    /*
     * mark as unflagged the conversation
     * string $userid - the logged in user id
     * string $folder - the current folder
     */

    public function unflag($userid, $folder) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));

        $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior

        $messages = $this->messages(array('scopes' => array($folder => $userid)));
        
        foreach ($messages as $message) {
            $message->unflag($userid, $folder);
        }

        return true;
    }

    /*
     *  clear all flags of the current conversation
     */
    public function clearFlags($userid) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));

        if ($this->initiator_id == $userid) {
            $this->initiator_del = 0;
            $this->initiator_spam = 0;
            $this->initiator_arch = 0;
            $this->initiator_restored = self::INITIATOR_FLAG;
        } else if (in_array($userid, $this->interlocutorIds)) {
            $mailboxInter = $this->mailboxInterlocutor($userid);
            $mailboxInter->interlocutor_del = 0;
            $mailboxInter->interlocutor_spam = 0;
            $mailboxInter->interlocutor_arch = 0;
        }
        else
            throw new CHttpException(400, t('cms', 'User denied'));

        return true;
    }
    
    /*
     *  mark as restored the current conversation
     */

    public function restore($userid) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));

        $this->clearFlags($userid);
        $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior

        if ($this->initiator_id == $userid) {
            $this->initiator_restored = self::INITIATOR_FLAG;
            $this->save();
        } else if (in_array($userid, $this->interlocutorIds)) {
            $mailboxInter = $this->mailboxInterlocutor($userid);
            $mailboxInter->save();
        }
        else
            throw new CHttpException(400, t('cms', 'User denied'));

        return true;
    }
    
    /*
     *  mark as spam the current conversation
     */

    public function markSpam($userid = 0, $folder) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));

        $this->clearFlags($userid); // we start from initials values
        $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior

        if ($this->initiator_id == $userid) {
            $this->initiator_restored = 1;
            $this->save();
        } else if (in_array($userid, $this->interlocutorIds)) {
            $mailboxInter = $this->mailboxInterlocutor($userid);
            $mailboxInter->save();
        }

        $messages = $this->messages(array('scopes' => array($folder => $userid)));

        foreach ($messages as $message) {
            $message->markSpam($userid);
        }

        return true;
    }

    /*
     *  mark the conversation as not spammed
     */
    public function unmarkSpam($userid = 0, $folder) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));

        $this->clearFlags($userid); // we start from initials values
        $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior

        if ($this->initiator_id == $userid) {
            $this->initiator_restored = 1;
            $this->save();
        } else if (in_array($userid, $this->interlocutorIds)) {
            $mailboxInter = $this->mailboxInterlocutor($userid);
            $mailboxInter->save();
        }

        $messages = $this->messages(array('scopes' => array($folder => $userid)));


        foreach ($messages as $message) {
            $message->unmarkSpam($userid);
        }

        return true;
    }

    /*
     * mark the conversation as archived
     */
    public function archive($userid = 0) {
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));
        $this->clearFlags($userid); // we start from initials values
        $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior
        if ($this->initiator_id == $userid) {
            $this->initiator_arch = self::INITIATOR_FLAG;
            $this->initiator_restored = 0;
            $this->save();
        } else if (in_array($userid, $this->interlocutorIds)) {
            $mailboxInter = $this->mailboxInterlocutor($userid);
            $mailboxInter->interlocutor_arch = self::INTERLOCUTOR_FLAG;
            $mailboxInter->save();
        }
        else
            throw new CHttpException(400, t('cms', 'User denied'));

        return true;
    }

    /*
     * mark the conversation as deleted
     */
    public function delete($userid = 0, $folder) {

        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));

        $this->clearFlags($userid); // we start from initials values
        $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior
        if ($this->initiator_id == $userid) {
            $this->initiator_restored = 0;
            $this->save();
        } else if (in_array($userid, $this->interlocutorIds)) {
            $mailboxInter = $this->mailboxInterlocutor($userid);
            $mailboxInter->save();
            //$this->modified = time();
            //$this->save();
        }

        $messages = $this->messages(array('scopes' => array($folder => $userid)));

        foreach ($messages as $message) {
            $message->delete($userid, $folder);
        }

        return true;
    }

    /*
     * delete permanently the current conversation
     */
    public function permanentDelete($userid = 0, $folder) {
        //die((int)Yii::app()->controller->module->recyclePeriod);
        if (!$userid)
            throw new CHttpException(400, t('cms', 'User Id must be supplied for delete method'));

        $this->clearFlags($userid); // we start from initials values
        $this->detachBehavior('CAdvancedArBehavior'); // we detach temporarily CAdvancedArBehavior

        if ($this->initiator_id == $userid) {
            $this->initiator_restored = 0;
            $this->save();
        } else if (in_array($userid, $this->interlocutorIds)) {
            $mailboxInter = $this->mailboxInterlocutor($userid);
            $mailboxInter->save();
        }

        $messages = $this->messages(array('scopes' => array($folder => $userid)));

        foreach ($messages as $message) {
            $message->permanentDelete($userid);
        }

        $this->destroy();

        return true;
    }

    /*
     * End button actions
     */

    /**
     * This method is used by the cron method in the MailboxModule class to determine when a conversation
     * should be marked as permanently deleted by a user depending on the recycling period defined in the
     * module configs by the attribute 'recyclePeriod'. And if both users have the conversation marked
     * as permanently deleted then remove the conversation from the database.
     * 
     * @param integer $mask 
     * @param boolean $string_presentation
     * @return string|array
     */
    public function recycle() {
        // check initiator trash
        if ($this->initiator_del > 0) {
            // if not within recycling period
            if (!self::withinDays(Yii::app()->controller->module->recyclePeriod, $this->initiator_del)) {
                // mark permanently deleted
                $this->initiator_del = null;
            }
        }
        // check interlocutor trash
        if ($this->interlocutor_del > 0) {
            // if not within recycling period
            if (!self::withinDays(Yii::app()->controller->module->recyclePeriod, $this->interlocutor_del)) {
                // mark permanently deleted
                $this->interlocutor_del = null;
            }
        }
        // if both marked as permanently deleted
        if (is_null($this->initiator_del) && is_null($this->interlocutor_del)) {
            // delete this conversation from database
            $this->destroy();
        } else {
            // otherwise save possible changes
            $this->save();
        }
    }

    public function destroy() {
        if (count($this->messages) == 0) {
            MailboxInterlocutor::model()->deleteAll('conversation_id=:conversationId', array(':conversationId' => $this->conversation_id));
            // delete conversation
            return parent::delete();
        }
    }

    /**
     * Get array of values that make up mask.
     * 
     * @param integer $mask 
     * @param boolean $string_presentation
     * @return string|array
     */
    public static function getBitMaskValues($mask, $string_presentation = true) {
        $available_mask_arr = array(self::INITIATOR_FLAG, self::INTERLOCUTOR_FLAG);

        if (in_array($mask, $available_mask_arr)) {
            $values_count = pow(2, 2);
            if ($string_presentation) {
                $return_arr = "";
                for ($i = 1; $i < $values_count; $i++)
                    if (($mask & $i) == $mask)
                        $return_arr .= $i . ", ";

                return substr($return_arr, 0, -2);
            }
            else {
                $return_arr = array();
                for ($i = 1; $i < $values_count; $i++)
                    if (($mask & $i) == $mask)
                        $return_arr[$i] = $i;
                return $return_arr;
            }
        }
    }

    public static function withinDays($period, $deleted_on) {
        $day = date("z") + 1;
        if ($day > 183)
            $day = $day - 183;
        elseif ($day < $deleted_on)
            $day += 183;

        if (($day - $deleted_on) <= $period)
            return true;

        return false;
    }

    public function afterFind() {
        if (!empty($this->interlocutors)) {
            foreach ($this->interlocutors as $n => $interlocutor)
                $this->interlocutorIds[] = $interlocutor->user_id;
        }

        parent::afterFind();
    }

    public function afterReply($userid, $messageid) {
        $inboxMessages = $this->messages(array('scopes' => array('inbox' => $userid)));

        if ($this->isSpam($userid, 'spam') && count($inboxMessages) <= 1) {
            $recipientObj = MailboxRecipient::model()->find(array(
                'condition' => 'message_id=:messageId AND recipient_id=:recipientId',
                'params' => array(':messageId' => $messageid, ':recipientId' => $userid))
            );
            if ($recipientObj) {
                $recipientObj->recipient_spam = Mailbox::INTERLOCUTOR_FLAG;
                $recipientObj->update(array('recipient_spam'));
            }
        }

        if (!in_array($userid, $this->interlocutorIds)) {
            $interlocutorObj = new MailboxInterlocutor;
            $interlocutorObj->conversation_id = $this->conversation_id;
            $interlocutorObj->interlocutor_id = $userid;
            $interlocutorObj->save();
        }

        return true;
    }

    public function afterCompose($userid, $messageid, $recipientid) {

        $user = User::model()->findByPk($recipientid);

        if (!is_null($user->spamIds) && in_array($userid, $user->spamIds)) {
            $recipientObj = MailboxRecipient::model()->find(array(
                'condition' => 'message_id=:messageId AND recipient_id=:recipientId',
                'params' => array(':messageId' => $messageid, ':recipientId' => $recipientid))
            );

            if ($recipientObj) {
                $recipientObj->recipient_spam = Mailbox::INTERLOCUTOR_FLAG;
                $recipientObj->update(array('recipient_spam'));
            }
        }

        return true;
    }

}