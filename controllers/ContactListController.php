<?php

class ContactListController extends FeController {

    /**
     * List of allowd default Actions for the user
     * @return type 
     */
    public function allowedActions() {
        return 'autocomplete,viewcontact, delete, compose';
    }

    // this function generates autocomplete tags for "to" field
    public function actionAutocomplete() {
        $q = strtolower($_GET["term"]);
        if (!$q)
            return;

        $user = User::model()->findByPk(user()->id);
        $contacts = $user->contactIds;

        $result = array();

        if (isset($_GET['conv-id'])) {

            $conv_id = $_GET['conv-id'];
            if (!is_int($conv_id = (int) $conv_id))
                continue;

            $conv = Mailbox::conversation($conv_id);
            
            if (!$conv)
                continue;
            
            $senders = $conv->replyTo(user()->id);
            foreach ($senders as $k => $sender) {

                $label = GxcHelpers::getDisplayName($k, true, true);

                if (strpos(strtolower($label), $q) !== false) {
                    array_push($result, array("id" => $k, "label" => $label));
                }
            }
        }

        foreach ($contacts as $k => $contact_id) {
            $label = GxcHelpers::getDisplayName($contact_id, true, true);
            if (strpos(strtolower($label), $q) !== false) {
                array_push($result, array("id" => $contact_id, "label" => $label));
            }

            if (count($result) > 11)
                break;
        }

        echo json_encode(array_unique($result));
    }

    /**
     * this function display the contact
     */
    public function actionViewcontact() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;

            $cid = $_POST['cid'];

            if (!is_int($cid = (int) $cid))
                continue;

            $user = User::model()->findByPk($cid);



            if ($user)
                $count++;

            if ($count) {

                
                $thumbnail = $user->profile->selectedImage(100, true);
                

                $display_name = GxcHelpers::getDisplayName($cid, true, true);
                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'success' => 1,
                        'display_name' => $display_name,
                        'thumbnail_url' => $thumbnail
                    ));
                    Yii::app()->end();
                }
            } else {

                $message = t('site', 'There was an error trying to view this contact');

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
     * this compose message for the selected contacts
     */
    public function actionCompose() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;
            $recipients = array();
            foreach ($_POST['cids'] as $contact_id) {
                if (!is_int($contact_id = (int) $contact_id))
                    continue;

                $user = User::model()->findByPk($contact_id);

                if ($user) {
                    $label = GxcHelpers::getDisplayName($contact_id, true, true);
                    $recipients[$contact_id] = $label;
                    $count++;
                }
            }
            if ($count) {

                app()->user->setState('recipients', $recipients);
                
                //app()->controller->redirect(array('page/render', 'slug' => 'messages', 'action' => 'compose'));

                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'success' => 1,
                        'header' => t('site', 'Atenzione!'),
                        'redirect' => 1,
                        //'r' => var_dump($result),
                        'redirect_url' => app()->createUrl('page/render', array('slug' => 'messages', 'action' => 'compose'))
                    ));
                    Yii::app()->end();
                }
            } else {

                $message = t('site', 'Error while trying to compose message to this contacts.');

                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'error' => $message,
                        'header' => t('site', 'Errore!')
                    ));
                    Yii::app()->end();
                }
                user()->setFlash('error', $message);
            }
        } else
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
    }

    /**
     * this function delete the selected contacts
     */
    public function actionDelete() {
        if (Yii::app()->request->isPostRequest) {
            $count = 0;

            foreach ($_POST['cids'] as $contact_id) {
                if (!is_int($contact_id = (int) $contact_id))
                    continue;

                $contact = ContactList::model()->find(array(
                    'condition' => 'owner_id=:userid AND contact_id=:contactid',
                    'params' => array(':userid' => user()->id, ':contactid' => $contact_id)
                        ));

                if ($contact->delete())
                    $count++;
            }
            if ($count) {
                if ($count > 1) {
                    $message = t('site', ':count contacts have been deleted from your list.', array(':count' => $count));
                } else {
                    $message = t('site', 'The contact has been deleted from your list.');
                }

                if (isset($_GET['ajax'])) {
                    user()->setFlash('info-ajax', $message);
                    echo json_encode(array(
                        'success' => $message,
                        'header' => t('site', 'Atenzione!'),
                        'refresh' => 1,
                            //'redirect_url' => app()->createUrl('page/render', array('slug' => 'messages', 'folder' => $folder))
                    ));
                    Yii::app()->end();
                }
                //user()->setFlash('success', $message);
            } else {

                $message = t('site', 'Error while trying to delete the contact(s).');

                if (isset($_GET['ajax'])) {
                    echo json_encode(array(
                        'error' => $message,
                        'header' => t('site', 'Errore!')
                    ));
                    Yii::app()->end();
                }

                user()->setFlash('error', $message);
            }
        } else
            throw new CHttpException(400, t('site', 'Invalid request. Please do not repeat this request again.'));
    }

}