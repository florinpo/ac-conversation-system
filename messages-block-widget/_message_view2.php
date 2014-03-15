<?php
// we clean the images session
if (Yii::app()->user->hasState('imagesMailbox')) {
    app()->user->setState('imagesMailbox', null);
}


//header("Content-type: image/jpeg");
//header('Content-Disposition: attachment; filename="'.$title.'-' . $timestamp . '.csv"');
//
//$url = 'http://localhost/gxc2/web/messages/?folder=sent&action=view&item-id=48';
////
//print_r(get_headers($url));

$conv = $data['conv'];
$models = $data['models'];
$messages = $data['messages'];
$files = $data['files'];
$subject = $conv->subject;

$mrclass = $conv->isNew(user()->id, $folder) ? 'visible' : 'hidden';
$muclass = $conv->isNew(user()->id, $folder) ? 'hidden' : 'visible';
$rfclass = $conv->isFlagged(user()->id, $folder) ? 'visible' : 'hidden';
$afclass = $conv->isFlagged(user()->id, $folder) ? 'hidden' : 'visible';

$cuser = User::model()->findByPk(user()->id);
$cuimage = ($cuser->user_type == 0) ? $cuser->profile->selectedImage(100) : $cuser->cshop->selectedImage(100, 'frontend');

/* * * $btn_back ** */
$btn_back = CHtml::link('<span class="icon-long-arrow-left">', app()->createUrl('page/render', array('slug' => 'messages', 'folder' => $folder)), array('class' => 'buttonM bDefault noLb btn-back'));

/* * * $btn_expand ** */
$btn_expand = CHtml::link("<span class='icon-format-2'></span><span>" . t('site', 'Expandi tutti') . "</span>", 'javascript:void(0)', array('id' => 'expand', 'class' => 'buttonM bDefault wLb btn-toogle floatR hidden'));

/* * * $btn_minimize ** */
$btn_minimize = CHtml::link("<span class='icon-format-4'></span><span>" . t('site', 'Comprimi tutti') . "</span>", 'javascript:void(0)', array('id' => 'minimize', 'class' => 'buttonM bDefault wLb btn-toogle floatR'));

/* * * $btn_restore ** */
$btn_inbox = CHtml::link('<span class="inner"><span class="text">'
                . t('site', 'Move to inbox') .
                '</span></span>', 'javascript:void(0)', array('class' => 'btn-n grey mailbox-btn', 'id' => 'restore'));

/* * * $btn_grp1 ** */
$btn_grp1 = "<div class='btn-group floatL'>"; // start btn-group
$btn_grp1 .= "<ul class='toolbar'>";
$btn_grp1 .= "<li>" . CHtml::link("<span class='icon-remove'></span><span>" . t('site', 'Elimina') . "</span>", 'javascript:void(0)', array('class' => 'buttonM bDefault wLb mailbox-btn', 'id' => 'delete')) . "</li>";
$btn_grp1 .= "<li>" . CHtml::link("<span class='icon-box-add'></span><span>" . t('site', 'Archivia') . "</span>", 'javascript:void(0)', array('class' => 'buttonM bDefault wLb mailbox-btn', 'id' => 'archive')) . "</li>";
$btn_grp1 .= "<li>" . CHtml::link("<span class='icon-spam'></span><span>" . t('site', 'Spam') . "</span>", 'javascript:void(0)', array('class' => 'buttonM bDefault wLb mailbox-btn', 'id' => 'markspam')) . "</li>";
$btn_grp1 .= "</ul></div>"; // end btn-group

/* * * $btn_grp2 ** */
$btn_grp2 = "<div class='btn-group floatL'>"; // start btn-group
$btn_grp2 .= "<ul class='toolbar'>";
$btn_grp2 .= "<li>" . CHtml::link("<span class='icon-box-add'></span><span>" . t('site', 'Move to inbox') . "</span>", 'javascript:void(0)', array('class' => 'buttonM bDefault wLb mailbox-btn', 'id' => 'archive')) . "</li>";
$btn_grp2 .= "<li>" . CHtml::link("<span class='icon-remove'></span><span>" . t('site', 'Elimina') . "</span>", 'javascript:void(0)', array('class' => 'buttonM bDefault wLb mailbox-btn', 'id' => 'delete')) . "</li>";
$btn_grp2 .= "<li>" . CHtml::link("<span class='icon-spam'></span><span>" . t('site', 'Spam') . "</span>", 'javascript:void(0)', array('class' => 'buttonM bDefault wLb mailbox-btn', 'id' => 'markspam')) . "</li>";
$btn_grp2 .= "</ul></div>"; // end btn-group


/* * * $btn_dropdown ** */
$btn_dropdown = "<div class='btn-group floatR'>"; // start dropdown
$btn_dropdown .= CHtml::link(t('site', 'Altro') . '<span class="caret"></span>', 'javascript:void(0)', array('class' => 'buttonM bDefault', 'data-toggle' => 'dropdown'));
$btn_dropdown .= "<ul id='nav-header' class='dropdown-menu'>";
$btn_dropdown .= "<li class=" . $mrclass . ">" . CHtml::link(t('site', 'Mark as read'), 'javascript:void(0)', array('id' => 'markread', 'class' => 'mailbox-btn')) . "</li>";
$btn_dropdown .= "<li class=" . $muclass . ">" . CHtml::link(t('site', 'Mark as unread'), 'javascript:void(0)', array('id' => 'markunread', 'class' => 'mailbox-btn')) . "</li>";
$btn_dropdown .= "<li class='li-flag " . $afclass . "'>" . CHtml::link(t('site', 'Add flag'), 'javascript:void(0)', array('id' => 'addflag', 'class' => 'mailbox-btn')) . "</li>";
$btn_dropdown .= "<li class='li-flag " . $rfclass . "'>" . CHtml::link(t('site', 'Remove flag'), 'javascript:void(0)', array('id' => 'removeflag', 'class' => 'mailbox-btn')) . "</li>";
$btn_dropdown .= "<li>" . CHtml::link(t('site', 'Add contacts'), 'javascript:void(0)', array('id' => 'addcontact', 'class' => 'mailbox-btn')) . "</li>";
$btn_dropdown .= "</ul>";
$btn_dropdown .= "</div>"; // end dropdown

/* * * $btn_spam ** */
$btn_spam = CHtml::link("<span class='icon-spam'></span><span>"
                . t('site', 'Spam') .
                "</span>", 'javascript:void(0)', array('class' => 'buttonM bDefault wLb single mailbox-btn', 'id' => 'markspam'));

/* * * $btn_no_spam ** */
$btn_no_spam = CHtml::link("<span class='icon-ok'></span><span>"
                . t('site', 'Not spam') .
                "</span>", 'javascript:void(0)', array('class' => 'buttonM bDefault wLb single mailbox-btn', 'id' => 'marknospam'));

/* * * $btn_perm_delete ** */
$btn_perm_delete = CHtml::link("<span class='icon-remove'></span><span>" . t('site', 'Elimina definitivamente') . "</span>", 'javascript:void(0)', array('class' => 'buttonM bDefault single wLb mailbox-btn', 'id' => 'permanentdelete'));

$actions = $btn_back;
// we show the $btn_perm_delete only for spam and trash folders
$actions .= (isset($folder) && $folder == 'spam') || (isset($folder) && $folder == 'trash') ? $btn_perm_delete : '';
$actions .= isset($folder) && $folder == 'trash' ? $btn_spam : '';
$actions .= isset($folder) && $folder == 'spam' ? $btn_no_spam : '';
$actions .= (isset($folder) && $folder == 'spam') || (isset($folder) && $folder == 'trash') || (isset($folder) && $folder == 'archived') ? '' : $btn_grp1;
$actions .= isset($folder) && $folder == 'archived' ? $btn_grp2 : '';

$actions .= $btn_expand;
$actions .= $btn_minimize;
$actions .= $btn_dropdown;
?>

<?php
$this->widget('cms.extensions.tooltipster.tooltipster', array(
    'identifier' => '.t-right',
    'options' => array(
        'position' => 'right',
        'trigger' => 'hover',
        'speed' => '150'
        ))
);
$this->widget('cms.extensions.tooltipster.tooltipster', array(
    'identifier' => '.t-left',
    'options' => array(
        'position' => 'left',
        'trigger' => 'hover',
        'speed' => '150'
        ))
);
?>
<div class="mailbox-conversation">
    <div class="mailbox-actions-wrapper clearfix">
        <div class="actions-left">
            <?php echo $actions; ?>
        </div>

    </div>

    <div class="mailbox-conversation-header clearfix">
        <div class="wrapper">
            <h2><?php echo $subject; ?></h2>
            <?php
            $rfclass = $conv->isFlagged(user()->id) ? 'visible' : 'hidden';
            echo CHtml::link("<span class='icon-flag'></span>", 'javascript:void(0)', array('class' => 'mailbox-btn mailbox-flag ' . $rfclass, 'id' => 'removeflag'));
            ?>
            <?php
            $afclass = $conv->isFlagged(user()->id) ? 'hidden' : 'visible';
            echo CHtml::link("<span class='icon-flag'></span>", 'javascript:void(0)', array('class' => 'mailbox-btn mailbox-flag ' . $afclass, 'id' => 'addflag'));
            ?>
        </div>
    </div>
    <ul class="mailbox-message-list clearfix">
        <?php
        $last_key = end(array_keys($messages));
        foreach ($messages as $k => $msg):
            ?>
            <?php
            //var_dump($msg->images);

            $sender = GxcHelpers::getDisplayName($msg->sender_id);

            $sendersIds = $msg->sendersIds(user()->id, true);
            $sendersLabels = $msg->sendersLabels(user()->id);

            $result = $msg->replyMultiple($conv->conversation_id, user()->id, $folder);

            $user = User::model()->findByPk($msg->sender_id);
            $thumbnail = ($user->user_type == 0) ? $user->profile->selectedImage(100) : $user->cshop->selectedImage(100, 'frontend');

            $msgClass = $msg->isFlagged(user()->id) ? 'flagged' : 'not-flagged';
            $msgClass .= ($k > 0 && count($messages) > 10 && $k < $last_key) ? ' hidden' : '';

            $images = $msg->images;
            ?>
            <?php if ($k == 1 && count($messages) > 10): ?>
                <li class="items-expand clearfix">
                    <?php
                    $counter = count($messages) - 2;
                    echo CHtml::link("<span class='icon icon-plus-sign'></span>" . t('site', ':counter altri messagi', array(':counter' => $counter)), 'javascript:void(0)', array('class' => 'bLink', 'id'=>'show-hidden'));
                    ?>
                </li>
            <?php else: ?>
                <li class="message-item clearfix <?php echo $msgClass; ?>" id="item-<?php echo $k; ?>">


                    <div class='loader-indicator loader-label-30'><span class='loader-txt'><?php echo t('site', 'Caricamento') ?><span></div>

                                <?php $class = ($last_key == $k) ? 'hidden' : 'visible'; ?>
                                <div class="minified clearfix <?php echo $class; ?>">
                                    <div class="col-1 floatL">
                                        <?php echo $sender; ?>
                                        <div class="content" style="display:inline-block">
                                            <?php echo str_trim($msg->text, 76); ?>
                                        </div>
                                    </div>
                                    <div class="col-2 floatR">
                                        <?php if (count($msg->images) > 0): ?>
                                            <span class="icon icon-attachment"></span>
                                        <?php endif; ?>
                                        <span class="date"><?php echo date("j M Y, H:i", $msg->created); ?></span>
                                        <?php
                                        $rclass = $msg->isFlagged(user()->id) ? 'visible' : 'hidden';
                                        echo CHtml::link("<span class='icon-flag'></span>", 'javascript:void(0)', array('class' => 'msg-btn item-flag ' . $rclass, 'id' => 'removeflag'));
                                        ?>
                                        <?php
                                        $aclass = $msg->isFlagged(user()->id) ? 'hidden' : 'visible';
                                        echo CHtml::link("<span class='icon-flag'></span>", 'javascript:void(0)', array('class' => 'msg-btn item-flag ' . $aclass, 'id' => 'addflag'));
                                        ?>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                                <?php $class = ($last_key == $k) ? 'visible' : 'hidden'; ?>
                                <div class="expanded clearfix <?php echo $class; ?>">
                                    <div class="thumbnail">
                                        <?php echo Chtml::link($thumbnail, ''); ?>
                                    </div>
                                    <div class="data-wrap" id="msg-<?php echo $msg->message_id; ?>">
                                        <div class="header clearfix">
                                            <div class="col-1 floatL sender" id="usr-<?php echo $msg->sender_id; ?>">
                                                <?php echo $sender; ?>
                                            </div>

                                            <div class="col-2 floatR">
                                                <?php if (count($msg->images) > 0): ?>
                                                    <span class="icon icon-attachment"></span>
                                                <?php endif; ?>
                                                <span class="date"><?php echo date("j M Y, H:i", $msg->created); ?></span>
                                                <?php
                                                $rclass = $msg->isFlagged(user()->id) ? 'visible' : 'hidden';
                                                echo CHtml::link("<span class='icon-flag'></span>", 'javascript:void(0)', array('class' => 'msg-btn item-flag ' . $rclass, 'id' => 'removeflag'));
                                                ?>
                                                <?php
                                                $aclass = $msg->isFlagged(user()->id) ? 'hidden' : 'visible';
                                                echo CHtml::link("<span class='icon-flag'></span>", 'javascript:void(0)', array('class' => 'msg-btn item-flag ' . $aclass, 'id' => 'addflag'));
                                                ?>
                                            </div>
                                            <div class="clear"></div>
                                            <div class="recipients">
                                                <span class="to"><?php echo t('site', 'A: '); ?></span>
                                                <?php
                                                if ($msg->sender_id == user()->id) {
                                                    echo $msg->sendersLabels(user()->id, true, false);
                                                } else {
                                                    echo t('site', 'me');
                                                }
                                                ?>
                                            </div>
                                        </div>

                                        <div class="content">
                                            <?php echo nl2br(makeLinks($msg->text)); ?>
                                        </div>

                                        <?php if (count($images) > 0): ?>
                                            <!-- The Gallery as lightbox dialog, should be a child element of the document body -->
                                            <div id="mailbox-gallery-<?php echo $k; ?>" class="blueimp-gallery blueimp-gallery-controls">
                                                <div class="slides"></div>
                                                <h3 class="title"></h3>
                                                <a class="prev">‹</a>
                                                <a class="next">›</a>
                                                <a class="close">×</a>
                                                <a class="play-pause"></a>
                                                <ol class="indicator"></ol>
                                            </div>
                                            <div class="msg-images">

                                                <ul class="images-list clearfix" id="links-<?php echo $k; ?>">
                                                    <?php foreach ($images as $image): ?>
                                                        <li class="img-item">
                                                            <?php
                                                            $path = IMAGES_URL . '/img100/' . $image->path;
                                                            //var_dump($path);
                                                            echo CHtml::link(CHtml::image($path, ''), IMAGES_URL . '/img400/' . $image->path, array('class' => 'gal-link'));
                                                            echo CHtml::link("<span class='icon iconl-download'></span>", app()->createUrl('message/download', array('img' => $image->id)), array('id' => 'download-file', 'class' => 'download'));
                                                            ?>

                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>

                                            </div>
                                        <?php endif; ?>

                                        <div class="actions">
                                            <?php echo CHtml::link("<span class='icon icon-reply'></span>" . t('site', 'Rispondi'), 'javascript:void(0)', array('class' => 'open-reply')); ?>
                                            <?php
                                            if ($msg->sender_id != user()->id):
                                                $cclass = $msg->isContact(user()->id) ? 'hidden' : 'visible';

                                                echo CHtml::link("<span class='icon-user-plus'></span>" . t('site', 'Aggiungi contatto'), 'javascript:void(0)', array('class' => 'msg-btn ' . $cclass, 'id' => 'addcontact'));
                                            endif;
                                            ?>
                                            <?php
                                            $delete_type = ($folder == 'trash') ? 'permanentdelete' : 'delete';
                                            echo CHtml::link("<span class='icon icon-remove'></span>" . t('site', 'Cancela questo messagio'), 'javascript:void(0)', array('class' => 'msg-btn', 'id' => $delete_type)); ?>
                                            <?php echo CHtml::link("<span class='icon icon-spam'></span>" . t('site', 'Segnala spam'), 'javascript:void(0)', array('class' => 'msg-btn', 'id' => 'markspam')); ?>
                                        </div>

                                    </div>
                                </div>

                                <div class="mailbox-reply-block clearfix hidden" id="reply-block-<?php echo $k; ?>">
                                    <div class="thumbnail-sender">
                                        <?php echo $cuimage; ?>
                                    </div>
                                    <div class="reply-form-wrapper">
                                        <?php
                                        $form = $this->beginWidget('CActiveForm', array(
                                            'action' => CHtml::normalizeUrl(array("page/render", 'slug' => 'messages', 'folder' => $folder, 'action' => 'view', 'item-id' => $conv->conversation_id, 'counter' => $k)),
                                            'htmlOptions' => array('autocomplete' => 'off', 'enctype' => 'multipart/form-data', 'class' => 'reply-message-form'),
                                            'id' => 'message-form-' . $k,
                                            'enableClientValidation' => false,
                                            'enableAjaxValidation' => true,
                                            'clientOptions' => array(
                                                'hideErrorMessage' => true,
                                                'validateOnSubmit' => true,
                                                'validateOnChange' => false,
                                                'validationUrl' => bu() . '/ajax?form-c=' . $k, //Point to any ajax page you want
                                                'beforeValidate' => 'js:function(form){
                                                        var item = form.parents(".message-item");
                                                        var expanded = item.find(".expanded");
                                                        var block = form.parents(".mailbox-reply-block");
                                                        var loader = item.find(".loader-indicator");
                                                        expanded.addClass("loading");
                                                        block.addClass("loading");
                                                        loader.show();
                                                        return true;
                                                    }',
                                                'afterValidate' => 'js:function(form,data,hasError){
                                                        var item = form.parents(".message-item");
                                                        var expanded = item.find(".expanded");
                                                        var block = form.parents(".mailbox-reply-block");
                                                        var loader = item.find(".loader-indicator");
                                                        if(!hasError){
                                                            var url = "' . CHtml::normalizeUrl(array("conversation/reply")) . '";
                                                            var data = form.serialize()+"&folder=' . $folder . '"+"&counter="+$.yiiconversation.counter;
                                                            $.yiiconversation.formCounter = "' . $k . '"; 
                                                            $.yiiconversation.submitAjaxForm(url, data);     
                                                        } else {
                                                            var item = form.parents(".message-item");
                                                            var expanded = item.find(".expanded");
                                                            var block = form.parents(".mailbox-reply-block"); 
                                                            expanded.removeClass("loading");
                                                            block.removeClass("loading");
                                                            loader.hide();
                                                        }
                                             }'
                                            )
                                                ));
                                        ?>
                                        <?php echo $form->errorSummary($models[$k], '', ''); ?>
                                        <div class="box-form">
                                            <?php echo $form->hiddenField($models[$k], 'conversation_id', array('value' => $conv->conversation_id)); ?>
                                            <span class="hidden"> <?php echo $form->error($models[$k], 'conversation_id'); ?></span>
                                            <div class="row lbs">
                                                <?php $formSender = GxcHelpers::getDisplayName($msg->sender_id, true, true); ?>
                                                <?php echo $form->hiddenField($models[$k], 'to', array('value' => $sendersIds)); ?>
                                                <?php echo CHtml::hiddenField('recipients', $sendersLabels, array('id' => 'Contacts_labels_' . $k)); ?>
                                                <div class="btn-group floatL">
                                                    <a class="buttonM bDefault noLb" data-toggle="dropdown" href="#"><span class="icon-reply"></span><span class="caret"></span></a>
                                                    <ul class="dropdown-menu">
                                                        <li><?php
                                        $sendersSingle = $msg->sendersLabels(user()->id, false);

                                        echo CHtml::link("<span class='icon-reply'></span>" . t('site', 'Rispondi a: ') . "<span class='tags'>" . $sendersSingle . "</span>", 'javascript:void(0)', array('class' => 'tags-btn', 'id' => 'sendersSingle'));
                                                ?></li>
                                                        <?php if ($result): ?>
                                                            <?php
                                                            $sendersMultiple = $msg->replyMultiple($conv->conversation_id, user()->id, $folder, false);
                                                            $sendersMultiple = implode(", ", $sendersMultiple);
                                                            ?>
                                                            <li><?php echo CHtml::link("<span class='icon-reply-multiple'></span>" . t('site', 'Rispondi ai mittenti: ') . "<span class='tags'>" . $sendersMultiple . "</span>", 'javascript:void(0)', array('class' => 'tags-btn', 'id' => 'sendersMultiple')); ?></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                                <div id="str-labels-<?php echo $k; ?>" class="usr-labels floatR"></div>
                                                <ul id="contacts-lbs-<?php echo $k; ?>" class="c-tags hidden"></ul>
                                                <span class="hidden"><?php echo $form->error($models[$k], 'to'); ?></span>
                                            </div>
                                            <div class="row">
                                                <?php echo $form->textArea($models[$k], 'body', array('class' => 'maibox-txt')); ?>
                                                <span class="hidden"><?php echo $form->error($models[$k], 'body'); ?></span>
                                            </div>
                                            <div class="row clearfix hidden">
                                                <div class="reply-upload-block">
                                                    <?php
                                                    $uploadWidget = $this->widget('cms.extensions.xupload.XUpload', array(
                                                        'url' => Yii::app()->createUrl("/message/upload", array('counter' => $k)),
                                                        //our XUploadForm
                                                        'model' => $files,
                                                        //We set this for the widget to be able to target our own form
                                                        'htmlOptions' => array('id' => 'message-form-' . $k),
                                                        'attribute' => 'uploadimg-' . $k,
                                                        'multiple' => true,
                                                        //Note that we are using a custom view for our widget
                                                        //Thats becase the default widget includes the 'form' 
                                                        //which we don't want here
                                                        'formView' => 'common.blocks.messages._upload_form',
                                                        'uploadView' => 'common.blocks.messages.upload_views.upload',
                                                        'downloadView' => 'common.blocks.messages.upload_views.download',
                                                        'options' => array(
                                                            'maxNumberOfFiles' => "js:$.yiiconversation.maxNumberOfFiles",
                                                            'maxFileSize' => 4000000,
                                                            'minFileSize' => ConstantDefine::UPLOAD_MIN_SIZE,
                                                            'autoUpload' => false,
                                                            'sequentialUploads' => true,
                                                            'acceptFileTypes' => "js:/(\.|\/)(jpe?g|png|gif)$/i"
                                                            )));
                                                    ?>
                                                </div>
                                            </div>
                                            <div class="actions-form clearfix">
                                                <?php echo CHtml::submitButton(t('site', 'Rispondi'), array('class' => 'buttonS bGreen floatL')); ?>
                                                <span class="vd-separator floatL"></span>

                                                <?php echo CHtml::link("<span class='icon icon-camera'></span>" . t('site', 'Add images'), 'javascript:void(0)', array('id' => 'selectfile', 'class' => 'bLink t-right select-file', 'title' => t('site', 'You can add 5 images. Max size 200 KB'))); ?>
                                                <div class="fileinput-button">
                                                    <?php
                                                    if ($uploadWidget->hasModel()) :
                                                        $uploadWidget->formOptions['accept'] = 'image/*';
                                                        echo CHtml::activeFileField($uploadWidget->model, $uploadWidget->attribute, $uploadWidget->formOptions) . "\n";
                                                    else :
                                                        echo CHtml::fileField($name, $uploadWidget->value, $uploadWidget->formOptions) . "\n";
                                                    endif;
                                                    ?>
                                                </div>
                                                <?php echo CHtml::link("<span class='icon-cancel'></span>", 'javascript:void(0)', array('class' => 'buttonS bDefault noLb floatR close-form t-left', 'title' => t('site', 'Anulla'))); ?>
                                            </div>
                                        </div>

                                        <?php $this->endWidget(); ?>
                                    </div>
                                </div>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </ul>
                        </div>
