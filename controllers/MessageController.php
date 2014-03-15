<?php

class MessageController extends FeController {

    /**
     * List of allowd default Actions for the user
     * @return type 
     */
    public function allowedActions() {
        return 'autocomplete,addcontact, addflag, removeflag, delete, permanentdelete, markspam, upload, download';
    }

    // this function generates autocomplete tags for "to" field
    public function actionAutocomplete() {
        $q = strtolower($_GET["term"]);
        if (!$q)
            return;


        $result = array();

        if (isset($_GET['conv-id'])) {

            $conv_id = $_GET['conv-id'];
            $folder = $_GET['folder'];
            if (!is_int($conv_id = (int) $conv_id))
                continue;

            $conv = Mailbox::conversation($conv_id);

            if (!$conv)
                continue;


            $recipients = Mailbox::conversationRecipients($conv_id, user()->id, true, true);


            foreach ($recipients as $k => $recipient) {

                $label = GxcHelpers::getDisplayName($k, true, true);

                if (strpos(strtolower($label), $q) !== false) {
                    array_push($result, array("id" => $k, "label" => $recipient));
                }
                if (count($result) > 10)
                    break;
            }
        }

        echo json_encode($result);
    }

    /**
     * this function save contacts from selected messages
     */
    public function actionAddcontact() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];

            foreach ($_POST['msgs'] as $msg_id) {

                if (!is_int($msg_id = (int) $msg_id))
                    continue;

                $message = Message::model()->findByPk($msg_id);

                // we check the user contacts
                $user = User::model()->findByPk(user()->id);
                $contacts = array();
                foreach ($user->contacts as $contact) {
                    $contacts[] = $contact->contact_id;
                }

                $senderId = $message->sender_id;
                if ($senderId != user()->id && !in_array($senderId, $contacts)) {
                    $contact = new ContactList;
                    $contact->owner_id = user()->id;
                    $contact->contact_id = $senderId;
                    $contact->save();
                    $count++;
                }
            }

            if ($count) {
                if ($count > 1) {
                    $message = t('site', ':count contacts have been added to your list.', array(':count' => $count));
                } else {
                    $message = t('site', 'The contact has been added to your list.');
                }
                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'success' => $message,
                        'header' => t('site', 'Atenzione!'),
                        'notification' => 1,
                        'redirect' => 0,
                        'type' => 'contact'
                    ));
                    Yii::app()->end();
                }
                //user()->setFlash('success', $message);
               
            } else {

                $message = t('site', 'No contact has been saved. Please make sure the selected contacts are not already in your list.');

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
     * this function add flag to selected messages
     */
    public function actionAddflag() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];

            foreach ($_POST['msgs'] as $msg_id) {

                if (!is_int($msg_id = (int) $msg_id))
                    continue;

                $message = Message::model()->findByPk($msg_id);

                if (!$message->belongsTo(user()->id))
                    continue;
                if (!$message->validate())
                    continue;
                if ($message->flag(user()->id, $folder))
                    $count++;
            }


            if ($count) {
                if ($count > 1) {
                    $message = t('site', ':count messages have been flagged.', array(':count' => $count));
                } else {
                    $message = t('site', 'The message has been flagged.');
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
                //user()->setFlash('success', $message);
                //$this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            } else {

                $message = t('site', 'Error while trying to flag the message(s).');

                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'error' => $message,
                        'header' => t('site', 'Errore!')
                    ));
                    Yii::app()->end();
                }
                //user()->setFlash('error', $message);
                //$this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            }
        } else
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
    }

    /**
     * this function remove the flag from selected messages
     */
    public function actionRemoveflag() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];
            foreach ($_POST['msgs'] as $msg_id) {

                if (!is_int($msg_id = (int) $msg_id))
                    continue;

                $message = Message::model()->findByPk($msg_id);

                if (!$message->belongsTo(user()->id))
                    continue;
                if (!$message->validate())
                    continue;
                if ($message->unflag(user()->id, $folder))
                    $count++;
            }
            if ($count) {
                if ($count > 1) {
                    $message = t('site', ':count messages have been unflagged.', array(':count' => $count));
                } else {
                    $message = t('site', 'The message has been unflagged.');
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
                //user()->setFlash('success', $message);
                //$this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            } else {

                $message = t('site', 'Error while trying to unflag the message(s).');


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
     * this function delete the selected messages
     */
    public function actionDelete() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];

            foreach ($_POST['msgs'] as $msg_id) {

                if (!is_int($msg_id = (int) $msg_id))
                    continue;

                $message = Message::model()->findByPk($msg_id);
                $conv = Mailbox::model()->findByPk($message->conversation_id);

                if (!$message->belongsTo(user()->id))
                    continue;
                if (!$message->validate())
                    continue;
                if ($message->delete(user()->id, $folder))
                    $count++;
            }
            if ($count) {
                $messages = $conv->messages(array('scopes' => array($folder => user()->id)));
                if ($count > 1) {
                    $message = t('site', ':count messages have been moved to trash.', array(':count' => $count));
                } else {
                    $message = t('site', 'The message has been moved to trash.');
                }
                if (isset($_GET['ajax'])) {

                    if (count($messages) == 0) {
                        user()->setFlash('info-ajax', $message);
                    }

                    echo json_encode(array(
                        'success' => $message,
                        'header' => t('site', 'Atenzione!'),
                        'notification' => count($messages) > 0 ? 1 : 0,
                        'redirect' => count($messages) > 0 ? 0 : 1,
                        'redirect_url' => app()->createUrl('page/render', array('slug' => 'messages', 'folder' => $folder)),
                        'clear' => 1
                    ));
                    Yii::app()->end();
                }
                //user()->setFlash('success', $message);
                //$this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            } else {

                $message = t('site', 'Error while trying to move the message(s) to the trash.');


                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'error' => $message,
                        'header' => t('site', 'Errore!')
                    ));
                    Yii::app()->end();
                }
                //user()->setFlash('error', $message);
                //$this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            }
        } else
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
    }
    
    /**
     * this function permanent delete the selected messages
     */
    public function actionPermanentdelete() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];

            foreach ($_POST['msgs'] as $msg_id) {

                if (!is_int($msg_id = (int) $msg_id))
                    continue;

                $message = Message::model()->findByPk($msg_id);
                $conv = Mailbox::model()->findByPk($message->conversation_id);

                if (!$message->belongsTo(user()->id))
                    continue;
                if (!$message->validate())
                    continue;
                if ($message->permanentDelete(user()->id, $folder))
                    $count++;
            }
            if ($count) {
                $messages = $conv->messages(array('scopes' => array($folder => user()->id)));
                if ($count > 1) {
                    $message = t('site', ':count messages have been permanently deleted.', array(':count' => $count));
                } else {
                    $message = t('site', 'The message has been permanently deleted.');
                }
                if (isset($_GET['ajax'])) {

                    if (count($messages) == 0) {
                        user()->setFlash('info-ajax', $message);
                    }

                    echo json_encode(array(
                        'success' => $message,
                        'header' => t('site', 'Atenzione!'),
                        'notification' => count($messages) > 0 ? 1 : 0,
                        'redirect' => count($messages) > 0 ? 0 : 1,
                        'redirect_url' => app()->createUrl('page/render', array('slug' => 'messages', 'folder' => $folder)),
                        'clear' => 1
                    ));
                    Yii::app()->end();
                }
                //user()->setFlash('success', $message);
                //$this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            } else {

                $message = t('site', 'Error while trying to move the message(s) to the trash.');


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
     * this function mark spam the selected messages
     */
    public function actionMarkspam() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $folder = $_POST['folder'];
            foreach ($_POST['msgs'] as $msg_id) {

                if (!is_int($msg_id = (int) $msg_id))
                    continue;

                $message = Message::model()->findByPk($msg_id);

                // we check the user contacts
                $user = User::model()->findByPk(user()->id);
                $contacts = array();
                foreach ($user->spammers as $contact) {
                    $contacts[] = $contact->user_id;
                }


                // if isset spam flag then we spam the user
                if (isset($_POST['spam'])) {
                    $senderId = $message->sender_id;
                    if ($senderId != user()->id && !in_array($senderId, $contacts)) {
                        $contact = new MailboxSpam;
                        $contact->user_id = user()->id;
                        $contact->spammer_id = $senderId;
                        $contact->save();
                    }
                }

                $conv = Mailbox::model()->findByPk($message->conversation_id);
                if (!$conv->belongsTo(user()->id))
                    continue;
                if (!$conv->validate())
                    continue;
                if ($conv->markSpam(user()->id))
                    $count++;
            }
            if ($count) {

                $message = t('site', 'The conversation has been marked as spam.');

                if (isset($_GET['ajax'])) {
                    user()->setFlash('info-ajax', $message);
                    echo json_encode(array(
                        'success' => $message,
                        'header' => t('site', 'Atenzione!'),
                        'notification' => 0,
                        'redirect' => 1,
                        'redirect_url' => app()->createUrl('page/render', array('slug' => 'messages', 'folder' => $folder))
                    ));
                    Yii::app()->end();
                }
                //user()->setFlash('success', $message);
                //$this->controller->redirect(array('page/render', 'slug' => 'messages', 'folder' => $folder));
            } else {

                $message = t('site', 'There was an error while trying to mark the conversation(s) as spam.');

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

    public function actionDownload() {
        $img_id = isset($_GET['img']) ? $_GET['img'] : '0';

        if ($img_id != 0) {
            $image = MailboxImage::model()->findByPk($img_id);
            $file = IMAGES_URL . '/img400/' . $image->path;
            header('Set-Cookie: fileDownload=true; path=/');
            header('Cache-Control: max-age=60, must-revalidate');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $image->name . '"');
            ob_clean();
            flush();
            readfile($file);
            exit;
        } else {
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
        }
    }

    /*
     * upload files to the message form
     */

    public function actionUpload() {
        Yii::import("cms.extensions.xupload.models.XUploadForm");
        //Here we define the paths where the files will be stored temporarily

        if (!(file_exists(IMAGES_FOLDER . DIRECTORY_SEPARATOR . 'tmp'))) {
            mkdir(IMAGES_FOLDER . DIRECTORY_SEPARATOR . 'tmp', 0777, true);
        }


        $path = IMAGES_FOLDER . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;

        $publicPath = IMAGES_URL . '/' . 'tmp/';

        $folder = 'mailbox';

        $sizes = ImageSize::getMailboxSizes();

//        $shop = UserCompanyShop::model()->find(array(
//            'condition' => 'companyId=:companyId',
//            'params' => array(':companyId' => user()->id)));
        //This is for IE which doens't handle 'Content-type: application/json' correctly
        header('Vary: Accept');
        if (isset($_SERVER['HTTP_ACCEPT'])
                && (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-type: application/json');
        } else {
            header('Content-type: text/plain');
        }

        //Here we check if we are deleting and uploaded file
        if (isset($_GET["_method"])) {
            if ($_GET["_method"] == "delete") {

                // if we delete the file then we also took out from user session
                if (Yii::app()->user->hasState('imagesMailbox')) {
                    $userImages = Yii::app()->user->getState('imagesMailbox');

                    foreach ($userImages as $k => $image) {
                        if ($userImages[$k]["filename"] == $_GET["file"]) {
                            echo "is true";
                            unset($userImages[$k]);
                            Yii::app()->user->setState('imagesMailbox', $userImages);
                        }
                    }
                }

                if ($_GET["file"][0] !== '.') {
                    foreach ($sizes as $size) {
                        $file = $path . $size['id'] . DIRECTORY_SEPARATOR . $_GET["file"];
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }
                }
                echo json_encode(true);
            }
        } else {
            $model = new XUploadForm;
            if (isset($_GET['counter'])) {
                $counter = $_GET['counter'];
                $model->file = CUploadedFile::getInstance($model, 'uploadimg-' . $counter);
            } else {
                $model->file = CUploadedFile::getInstance($model, 'uploadimg');
            }

            //We check that the file was successfully uploaded
            if ($model->file !== null) {
                //Grab some data
                $model->mime_type = $model->file->getType();
                $model->size = $model->file->getSize();
                $model->name = $model->file->getName();

                //(optional) Generate a random name for our file
                $filename = gen_uuid() . '_' . md5(Yii::app()->user->id . microtime() . $model->name);
                $filename .= "." . $model->file->getExtensionName();
                if ($model->validate()) {
                    //Move our file to our temporary dir
                    $model->file->saveAs($path . $filename);
                    chmod($path . $filename, 0775);

                    foreach ($sizes as $size) {

                        Yii::import('cms.extensions.image.Image');
                        $thumbs = new Image($path . $filename);

                        // we check the image dimension here
                        $cur_size = getimagesize($path . $filename);
                        
                        $curr_width = $cur_size[0];
                        $curr_height = $cur_size[1];

                        if ($size['width'] == '100') {
                            $thumbs->smart_resize($size['width'], $size['height'])->quality($size['quality']);
                        } elseif ($curr_width < $size['width']) {
                            $thumbs->square_fixed($size['width'], $size['height'])->quality($size['quality']);
                        } else {
                            $thumbs->square()->resize($size['width'], $size['height'])->quality($size['quality']);
                        }


                        $sizePath = $path . DIRECTORY_SEPARATOR . $size['id'];


                        if (!(file_exists($sizePath) && ($sizePath))) {
                            mkdir($sizePath, 0775, true);
                        }

                        if (!(file_exists($sizePath . DIRECTORY_SEPARATOR . 'index.html'))) {
                            $fp = fopen($sizePath . DIRECTORY_SEPARATOR . 'index.html', 'w'); // open in write mode.
                            fclose($fp); // close the file.
                        }
                        $thumbs->save($sizePath . DIRECTORY_SEPARATOR . $filename);
                    }

                    //unlink($path . DIRECTORY_SEPARATOR . $filename);
                    // Now we need to save this path to the user's session
                    if (Yii::app()->user->hasState('imagesMailbox')) {
                        $userImages = Yii::app()->user->getState('imagesMailbox');
                    } else {
                        $userImages = array();
                    }
                    $userImages[] = array(
                        "path" => $path . $filename,
                        "100" => $path . 'img100' . DIRECTORY_SEPARATOR . $filename,
                        "400" => $path . 'img400' . DIRECTORY_SEPARATOR . $filename,
                        "filename" => $filename,
                        'size' => $model->size,
                        'mime' => $model->mime_type,
                        'name' => $model->name,
                        'extension' => $model->file->getExtensionName(),
                    );
                    Yii::app()->user->setState('imagesMailbox', $userImages);


                    //Now we need to tell our widget that the upload was succesfull
                    //We do so, using the json structure defined in
                    // https://github.com/blueimp/jQuery-File-Upload/wiki/Setup
                    echo json_encode(array(array(
                            "name" => $model->name,
                            "type" => $model->mime_type,
                            "size" => $model->size,
                            //"url" => $publicPath . $filename,
                            "thumbnail_url" => $publicPath . "img100/" . $filename,
                            "delete_url" => app()->createUrl("message/upload", array(
                                "_method" => "delete",
                                "file" => $filename,
                                    //'YII_CSRF_TOKEN' => Yii::app()->getRequest()->getCsrfToken(),
                            )),
                            "delete_type" => "POST",
                            )));
                } else {
                    //If the upload failed for some reason we log some data and let the widget know
                    echo json_encode(array(
                        array("error" => $model->getErrors('file'),
                            )));
                    Yii::log("XUploadAction: " . CVarDumper::dumpAsString($model->getErrors()), CLogger::LEVEL_ERROR, "cms.extensions.xupload.actions.XUploadAction"
                    );
                }
            } else {
                throw new CHttpException(500, "Could not upload file");
            }
        }
    }

}