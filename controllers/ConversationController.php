<?php

class ConversationController extends FeController {

    /**
     * List of allowd default Actions for the user
     * @return type 
     */
    public function allowedActions() {
        return 'addcontact, reply, markread, markunread, markspam, marknospam, restore, 
               archive, delete, permanentdelete, addflag, removeflag';
    }

    public function actionAddcontact() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];
            foreach ($_POST['convs'] as $conversation_id) {
                if (!is_int($conversation_id = (int) $conversation_id))
                    continue;

                // we check the user contacts
                $user = User::model()->findByPk(user()->id);
                $contacts = array();
                foreach ($user->contacts as $contact) {
                    $contacts[] = $contact->contact_id;
                }

                $senders = Mailbox::conversationSenders($conversation_id, user()->id, 'inbox', false, false);
                if (count($senders) > 0) {
                    foreach ($senders as $k => $sender) {
                        if (!in_array($k, $contacts)) {
                            $contact = new ContactList;
                            $contact->owner_id = user()->id;
                            $contact->contact_id = $k;
                            $contact->save();
                            $count++;
                        }
                    }
                }
            }
            if ($count) {
                if ($count > 1) {
                    $message = t('site', ':count contacts have been added to your list.', array(':count' => $count));
                } else {
                    $message = t('site', '1 contact has been added to your list.');
                }
                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'success' => $message,
                        'header' => t('site', 'Atenzione!'),
                        'type' => 'contact',
                        'redirect' => 0,
                        'notification' => 1
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('success', $message);
                $this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            } else {

                $message = t('site', 'Please make sure the selected contacts are not already in your list.');

                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'error' => $message,
                        'header' => t('site', 'Errore!')
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('error', $message);
                $this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            }
        } else
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
    }

    /**
     * this function will handle the ajax reply in conversation view
     */
    public function actionReply() {
        if (Yii::app()->request->isPostRequest) {
            $body = $_POST['MessageForm']['body'];
            $conv_id = $_POST['MessageForm']['conversation_id'];
            $to = $_POST['MessageForm']['to'];
            $conv = Mailbox::conversation($conv_id);
            $folder = $_POST['folder'];
            $counter = $_POST['counter'];

            $reply = new Message;
            $reply->text = $body;
            $reply->conversation_id = $conv->conversation_id;
            $reply->recipients = explode(',', $to);
            $reply->sender_id = user()->id;
            $reply->created = time();
            $reply->crc64 = Message::crc64($body);

            $conv->modified = $reply->created;

            $receivedMessages = Mailbox::conversationReceivedMessages($conv->conversation_id, user()->id);

            if (count($receivedMessages) > 0 && in_array($conv->initiator_id, $reply->recipients)) {
                $conv->initiator_restored = Mailbox::INITIATOR_FLAG;
            }

            $validate = $reply->validate();
            $validate = $conv->validate() && $validate;

            if ($validate) {
                $conv->save();
                if ($reply->save()) {
                    foreach ($reply->recipients as $id) {
                        $conv->afterReply($id, $reply->message_id);
                    }
                }

                $message = t('site', 'Your message has been successfully sent.');


                $model = new MessageForm('reply');

                $js = <<<EOD
                      jQuery(function($) {
                            jQuery('#message-form-{$counter}').yiiactiveform({
                                'hideErrorMessage':true,
                                'validateOnSubmit':true,
                                'validateOnChange':false,
                                'validationUrl':'/gxc2/web/ajax?form-c={$counter}',
                                'beforeValidate': function(form){
                                                    var item = form.parents(".message-item");
                                                    var expanded = item.find(".expanded");
                                                    var block = form.parents(".mailbox-reply-block");
                                                    var loader = item.find(".loader-indicator");
                                                    expanded.addClass("loading");
                                                    block.addClass("loading");
                                                    loader.show();
                                                    return true;
                                },
                                'afterValidate':function(form,data,hasError){
                                                var item = form.parents(".message-item");
                                                var expanded = item.find(".expanded");
                                                var block = form.parents(".mailbox-reply-block");
                                                var loader = item.find(".loader-indicator");
                                                if(!hasError){
                                                    var url = "/gxc2/web/conversation/reply/";
                                                    var data = form.serialize()+'&folder={$folder}'+'&counter='+$.yiiconversation.counter;
                                                    $.yiiconversation.formCounter = {$counter}; 
                                                    $.yiiconversation.submitAjaxForm(url, data);     
                                                } else {
                                                    var item = form.parents(".message-item");
                                                    var expanded = item.find(".expanded");
                                                    var block = form.parents(".mailbox-reply-block"); 
                                                    expanded.removeClass("loading");
                                                    block.removeClass("loading");
                                                    loader.hide();
                                                }
                                },
                                'attributes':[{'id':'MessageForm_conversation_id','inputID':'MessageForm_conversation_id','errorID':'MessageForm_conversation_id_em_','model':'MessageForm','name':'conversation_id','enableAjaxValidation':true},{'id':'MessageForm_to','inputID':'MessageForm_to','errorID':'MessageForm_to_em_','model':'MessageForm','name':'to','enableAjaxValidation':true},{'id':'MessageForm_body','inputID':'MessageForm_body','errorID':'MessageForm_body_em_','model':'MessageForm','name':'body','enableAjaxValidation':true}],
                                'summaryID':'message-form-{$counter}_es_'
                            });
                            
                            jQuery('#message-form-{$counter}').fileupload({'maxNumberOfFiles':$.yiiconversation.maxNumberOfFiles,'maxFileSize':4000000,'minFileSize':4000,'autoUpload':true,'sequentialUploads':true,'acceptFileTypes':/(\.|\/)(jpe?g|png|gif)$/i,'url':'/gxc2/web/message/upload/?counter={$counter}'});
                            jQuery(".t-right").tooltipster({'position':'right', 'speed':'150'});
                            jQuery(".t-left").tooltipster({'position':'left', 'speed':'150'});
                       });
EOD;

                Yii::import("cms.extensions.xupload.models.XUploadForm");
                $files = new XUploadForm;

                $output = $this->renderPartial("common.blocks.messages._reply-form", array(
                    'model' => $model,
                    'counter' => $counter,
                    'folder' => $folder,
                    'conv' => $conv,
                    'id' => $reply->message_id,
                    'files' => $files
                        ), true);



                echo json_encode(array(
                    'success' => $message,
                    'header' => t('site', 'Conferma!'),
                    'counter' => $counter,
                    'output' => $output,
                    'js' => $js
                ));
                app()->end();
            } else {
                $message = t('site', 'Error while sending the message.');
                echo json_encode(array('error' => $message, 'header' => t('site', 'Errore!')));
                app()->end();
            }
        } else
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
    }

    /**
     * this function mark as read the selected conversations
     */
    public function actionMarkread() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];
            foreach ($_POST['convs'] as $conversation_id) {
                if (!is_int($conversation_id = (int) $conversation_id))
                    continue;
                $conv = Mailbox::model()->findByPk($conversation_id);

                if (!$conv->belongsTo(user()->id))
                    continue;
                if ($conv->read(user()->id, $folder) || $conv->validate())
                    $count++;
            }
            if ($count) {
                if ($count > 1) {
                    $message = t('site', ':count conversations have been marked as read.', array(':count' => $count));
                } else {
                    $message = t('site', 'The conversation has been marked as read.');
                }
                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'success' => $message,
                        'header' => t('site', 'Atenzione!'),
                        'redirect' => 0,
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('success', $message);
                //$this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            } else {
                $message = t('site', 'Error while trying to mark the conversation(s) as read.');

                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'error' => $message,
                        'header' => t('site', 'Errore!')
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('error', $message);
                //$this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            }
        } else
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
    }

    /**
     * this function mark as unread the selected conversations
     */
    public function actionMarkunread() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];
            foreach ($_POST['convs'] as $conversation_id) {
                if (!is_int($conversation_id = (int) $conversation_id))
                    continue;
                $conv = Mailbox::model()->findByPk($conversation_id);

                if (!$conv->belongsTo(user()->id))
                    continue;
                if ($conv->unread(user()->id, $folder) || $conv->validate())
                    $count++;
            }
            if ($count) {
                if ($count > 1) {
                    $message = t('site', ':count conversations have been marked as unread.', array(':count' => $count));
                } else {
                    $message = t('site', 'The conversation has been marked as unread.');
                }
                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'success' => $message,
                        'header' => t('site', 'Atenzione!'),
                        'redirect' => 0,
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('success', $message);
                $this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            } else {
                $message = t('site', 'Error while trying to mark the conversation(s) as unread.');

                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'error' => $message,
                        'header' => t('site', 'Errore!')
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('error', $message);
                $this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            }
        } else
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
    }

    /**
     * this function add flag to selected conversations
     */
    public function actionAddflag() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];
            foreach ($_POST['convs'] as $conversation_id) {
                if (!is_int($conversation_id = (int) $conversation_id))
                    continue;
                $conv = Mailbox::model()->findByPk($conversation_id);


                if (!$conv->belongsTo(user()->id))
                    continue;
                if ($conv->flag(user()->id, $folder) || $conv->validate())
                    $count++;
            }
            if ($count) {
                if ($count > 1) {
                    $message = t('site', ':count conversations have been flagged.', array(':count' => $count));
                } else {
                    $message = t('site', 'The conversation has been flagged.');
                }
                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'success' => $message,
                        'header' => t('site', 'Atenzione!'),
                        'notification' => 0,
                        'redirect' => 0,
                        'type' => 'flag'
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('success', $message);
                //$this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            } else {

                $message = t('site', 'Error while trying to flag the conversation(s).');

                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'error' => $message,
                        'header' => t('site', 'Errore!')
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('error', $message);
                //$this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            }
        } else
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
    }

    /**
     * this function remove the flag from selected conversations
     */
    public function actionRemoveflag() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];
            foreach ($_POST['convs'] as $conversation_id) {
                if (!is_int($conversation_id = (int) $conversation_id))
                    continue;
                $conv = Mailbox::model()->findByPk($conversation_id);


                if (!$conv->belongsTo(user()->id))
                    continue;
                if ($conv->unflag(user()->id, $folder) || $conv->validate())
                    $count++;
            }
            if ($count) {
                if ($count > 1) {
                    $message = t('site', ':count conversations have been unflagged.', array(':count' => $count));
                } else {
                    $message = t('site', 'The conversation has been unflagged.');
                }
                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'success' => $message,
                        'header' => t('site', 'Atenzione!'),
                        'notification' => 0,
                        'redirect' => 0,
                        'type' => 'flag'
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('success', $message);
                //$this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            } else {

                $message = t('site', 'Error while trying to unflag the conversation(s).');

                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'error' => $message,
                        'header' => t('site', 'Errore!')
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('error', $message);
                //$this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            }
        } else
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
    }

    /**
     * this function delete the selected conversations
     */
    public function actionRestore() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];
            foreach ($_POST['convs'] as $conversation_id) {
                if (!is_int($conversation_id = (int) $conversation_id))
                    continue;

                $conv = Mailbox::model()->findByPk($conversation_id);

                if (!$conv->belongsTo(user()->id))
                    continue;
                if (!$conv->validate())
                    continue;
                if ($conv->restore(user()->id))
                    $count++;
            }
            if ($count) {
                if ($count > 1) {
                    $message = t('site', ':count conversations have been restored.', array(':count' => $count));
                } else {
                    $message = t('site', 'The conversation has been restored.');
                }

                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'success' => $message,
                        'header' => t('site', 'Atenzione!'),
                        'redirect' => 1,
                        'redirect_url' => app()->createUrl('page/render', array('slug' => 'messages', 'folder' => $folder))
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('success', $message);
                $this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            } else {

                $message = t('site', 'Error while trying to restore the conversation(s).');


                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'error' => $message,
                        'header' => t('site', 'Errore!'),
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('error', $message);
                $this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            }
        } else
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
    }

    /**
     * this function is to archive the selected conversations
     */
    public function actionArchive() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];
            foreach ($_POST['convs'] as $conversation_id) {
                if (!is_int($conversation_id = (int) $conversation_id))
                    continue;
                $conv = Mailbox::model()->findByPk($conversation_id);

                if (!$conv->belongsTo(user()->id))
                    continue;
                if ($conv->archive(user()->id) || $conv->validate())
                    $count++;
            }
            if ($count) {
                if ($count > 1) {
                    $message = t('site', ':count conversations have been archived.', array(':count' => $count));
                } else {
                    $message = t('site', 'The conversation has been archived.');
                }

                if (isset($_GET['ajax'])) {
                    user()->setFlash('info-ajax', $message);
                    echo json_encode(array(
                        'success' => $message,
                        'header' => t('site', 'Atenzione!'),
                        'redirect' => 1,
                        'redirect_url' => app()->createUrl('page/render', array('slug' => 'messages', 'folder' => $folder))
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('success', $message);
                $this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            } else {

                $message = t('site', 'Error while trying to archive the conversation(s).');


                if (isset($_GET['ajax'])) {
                    user()->setFlash('info-ajax', $message);
                    echo json_encode(array(
                        'error' => $message,
                        'header' => t('site', 'Errore!')
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('error', $message);
                $this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            }
        } else
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
    }

    /**
     * this function marks as spam the selected conversations
     */
    public function actionMarkspam() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];

            foreach ($_POST['convs'] as $conversation_id) {
                if (!is_int($conversation_id = (int) $conversation_id))
                    continue;

                $conv = Mailbox::model()->findByPk($conversation_id);

                // we check the user contacts
                $user = User::model()->findByPk(user()->id);
                $contacts = array();
                foreach ($user->spammers as $contact) {
                    $contacts[] = $contact->user_id;
                }
//
//
//                // if isset spam flag then we spam the user
                if (isset($_POST['spam'])) {
                    $senderId = $conv->lastReceived(user()->id, $folder)->sender_id;
                    if (!in_array($senderId, $contacts)) {
                        $contact = new MailboxSpam;
                        $contact->user_id = user()->id;
                        $contact->spammer_id = $senderId;
                        $contact->save();
                    }
                }

                if (!$conv->belongsTo(user()->id))
                    continue;
                if (!$conv->validate())
                    continue;
                if ($conv->markSpam(user()->id, $folder))
                    $count++;
            }
            if ($count) {
                if ($count > 1) {
                    $message = t('site', ':count conversations have been marked as spam.', array(':count' => $count));
                } else {
                    $message = t('site', 'The conversation has been marked as spam.');
                }

                if (isset($_GET['ajax'])) {
                    user()->setFlash('info-ajax', $message);
                    echo json_encode(array(
                        'success' => $message,
                        'header' => t('site', 'Atenzione!'),
                        'redirect' => 1,
                        'redirect_url' => app()->createUrl('page/render', array('slug' => 'messages', 'folder' => $folder))
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('success', $message);
                $this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            } else {

                $message = t('site', 'Error while trying to mark the conversation(s) as spam.');


                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'error' => $message,
                        'header' => t('site', 'Errore!')
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('error', $message);
                $this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            }
        } else
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
    }

    /**
     * this function unmarks as spam the selected conversations
     */
    public function actionMarknospam() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];
            foreach ($_POST['convs'] as $conversation_id) {
                if (!is_int($conversation_id = (int) $conversation_id))
                    continue;
                $conv = Mailbox::model()->findByPk($conversation_id);


                $user = User::model()->findByPk(user()->id);
                $contacts = array();
                foreach ($user->spammers as $contact) {
                    $contacts[] = $contact->user_id;
                }

                $senders = Mailbox::conversationSenders($conv->conversation_id, user()->id, $folder, false, true);
                if (count($senders) > 0) {
                    foreach ($senders as $k => $sender) {
                        if (in_array($k, $contacts)) {
                            MailboxSpam::model()->deleteAll(array(
                                'condition' => 'user_id=:userid AND spammer_id=:spammerid',
                                'params' => array(':userid' => user()->id, ':spammerid' => $k)
                            ));
                        }
                    }
                }

                if (!$conv->belongsTo(user()->id))
                    continue;
                if (!$conv->validate())
                    continue;
                if ($conv->unmarkSpam(user()->id, $folder))
                    $count++;
            }
            if ($count) {
                if ($count > 1) {
                    $message = t('site', ':count conversations have been unmarked as spam and moved to the inbox.', array(':count' => $count));
                } else {
                    $message = t('site', 'The conversation has been unmarked as spam and moved to the inbox.');
                }

                if (isset($_GET['ajax'])) {
                    user()->setFlash('info-ajax', $message);
                    echo json_encode(array(
                        'success' => $message,
                        'header' => t('site', 'Atenzione!'),
                        'redirect' => 1,
                        'redirect_url' => app()->createUrl('page/render', array('slug' => 'messages', 'folder' => $folder))
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('success', $message);
                $this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            } else {

                $message = t('site', 'Error while trying to move the conversation(s) to inbox.');

                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'error' => $message,
                        'header' => t('site', 'Errore!')
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('error', $message);
                $this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            }
        } else
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
    }

    /**
     * this function delete the selected conversations
     */
    public function actionDelete() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];

            foreach ($_POST['convs'] as $conversation_id) {
                if (!is_int($conversation_id = (int) $conversation_id))
                    continue;
                $conv = Mailbox::model()->findByPk($conversation_id);

                if (!$conv->belongsTo(user()->id))
                    continue;
                if (!$conv->validate())
                    continue;
                if ($conv->delete(user()->id, $folder))
                    $count++;
            }
            if ($count) {
                if ($count > 1) {
                    $message = t('site', ':count conversations have been moved to the trash.', array(':count' => $count));
                } else {
                    $message = t('site', 'The conversation has been moved to the trash.');
                }

                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'success' => $message,
                        'header' => t('site', 'Atenzione!'),
                        'redirect' => 1,
                        'redirect_url' => app()->createUrl('page/render', array('slug' => 'messages', 'folder' => $folder))
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('success', $message);
                $this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            } else {

                $message = t('site', 'Error while trying to move the conversation(s) to the trash.');

                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'error' => $message,
                        'header' => t('site', 'Errore!')
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('error', $message);
                $this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            }
        } else
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
    }

    /**
     * this function delete permanently the selected conversations
     */
    public function actionPermanentdelete() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];

            foreach ($_POST['convs'] as $conversation_id) {
                if (!is_int($conversation_id = (int) $conversation_id))
                    continue;
                $conv = Mailbox::model()->findByPk($conversation_id);


                if (!$conv->belongsTo(user()->id))
                    continue;
                if (!$conv->validate())
                    continue;

                if ($conv->permanentDelete(user()->id, $folder))
                    $count++;
            }
            if ($count) {
                if ($count > 1) {
                    $message = t('site', ':count conversations have been deleted.', array(':count' => $count));
                } else {
                    $message = t('site', 'The conversation has been deleted.');
                }

                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'success' => $message,
                        'header' => t('site', 'Atenzione!'),
                        'redirect' => 1,
                        'redirect_url' => app()->createUrl('page/render', array('slug' => 'messages', 'folder' => $folder))
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('success', $message);
                $this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            } else {

                $message = t('site', 'Error while deleting the conversation(s).');

                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'error' => $message,
                        'header' => t('site', 'Errore!')
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('error', $message);
                $this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            }
        } else
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
    }

}