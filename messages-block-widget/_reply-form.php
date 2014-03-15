<?php
$msg = Message::model()->findByPk($id);

$images = $msg->images;

$cuser = User::model()->findByPk(user()->id); // current user
$cuimage = ($cuser->user_type == 0) ? $cuser->profile->selectedImage(100) : $cuser->cshop->selectedImage(100, 'frontend');

$sender = GxcHelpers::getDisplayName($msg->sender_id);
$user = User::model()->findByPk($msg->sender_id);
$image = ($user->user_type == 0) ? $user->profile->selectedImage(100) : $user->cshop->selectedImage(100, 'frontend');
$msgClass = $msg->isFlagged(user()->id) ? 'flagged' : 'not-flagged';

$sendersIds = $msg->sendersIds(user()->id, true);
$sendersLabels = $msg->sendersLabels(user()->id);

$result = $msg->replyMultiple($conv->conversation_id, user()->id, $folder);
?>

<li class="message-item clearfix <?php echo $msgClass; ?>" id="item-<?php echo $counter; ?>">
    <div class='loader-indicator loader-label-30'><span class='loader-txt'><?php echo t('site', 'Caricamento') ?><span></div>
    <div class="minified hidden clearfix">
        <div class="col-1 floatL">
            <?php echo $sender; ?>
            <div class="content" style="display:inline-block">
                <?php echo str_trim($msg->text, 76); ?>
            </div>
        </div>
        <div class="col-2 floatR">
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
    <div class="expanded clearfix">
        <div class="thumbnail">
            <?php echo Chtml::link($image, ''); ?>
        </div>
        <div class="data-wrap" id="msg-<?php echo $msg->message_id; ?>">
            <div class="header clearfix">
                <div class="col-1 floatL sender" id="usr-<?php echo $msg->sender_id; ?>">
                    <?php echo $sender; ?>

                </div>
                <div class="col-2 floatR">
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
                <div id="blueimp-gallery" class="blueimp-gallery blueimp-gallery-controls">
                    <div class="slides"></div>
                    <h3 class="title"></h3>
                    <a class="prev">‹</a>
                    <a class="next">›</a>
                    <a class="close">×</a>
                    <a class="play-pause"></a>
                    <ol class="indicator"></ol>
                </div>
                <div class="msg-images">
                    <ul class="images-list clearfix" id="links">
                        <?php foreach ($images as $image): ?>
                            <li class="img-item">
                                <?php
                                $path = IMAGES_URL . '/img100/' . $image->path;
                                //var_dump($path);
                                echo CHtml::link(CHtml::image($path, ''), IMAGES_URL . '/img400/' . $image->path, array());
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
                <?php echo CHtml::link("<span class='icon icon-remove'></span>" . t('site', 'Cancela questo messagio'), 'javascript:void(0)', array('class' => 'msg-btn', 'id' => 'delete')); ?>
                <?php echo CHtml::link("<span class='icon icon-spam'></span>" . t('site', 'Segnala spam'), 'javascript:void(0)', array('class' => 'msg-btn', 'id' => 'markspam')); ?>
            </div>

        </div>
    </div>

    <div class="mailbox-reply-block clearfix hidden" id="reply-block-<?php echo $counter; ?>">
        <div class="thumbnail-sender">
            <?php echo $cuimage; ?>
        </div>
        <div class="reply-form-wrapper">
            <?php
            $form = $this->beginWidget('CActiveForm', array(
                'action' => CHtml::normalizeUrl(array("page/render", 'slug' => 'messages', 'folder' => $folder, 'action' => 'view', 'item-id' => $conv->conversation_id, 'counter' => $counter)),
                'htmlOptions' => array('autocomplete' => 'off', 'class' => 'reply-message-form'),
                'id' => 'message-form-' . $counter,
                'enableClientValidation' => false,
                'enableAjaxValidation' => true,
                'clientOptions' => array(
                    'hideErrorMessage' => true,
                    'validateOnSubmit' => true,
                    'validateOnChange' => false,
                    'validationUrl' => bu() . '/ajax?form-c=' . $counter, //Point to any ajax page you want
                    'afterValidate' => 'js:function(form,data,hasError){
                                        if(!hasError){
                                            var url = "' . CHtml::normalizeUrl(array("conversation/reply")) . '";
                                           
                                            var data = form.serialize()+"&folder=' . $folder . '"+"&counter="+$.yiiconversation.counter;
                                            $.yiiconversation.submitAjaxForm(url, data);     
                                        }
                                }'
                )
                    ));
            ?>
            <?php echo $form->errorSummary($model, '', ''); ?>
            <div class="box-form">
                <?php echo $form->hiddenField($model, 'conversation_id', array('value' => $conv->conversation_id)); ?>
                <?php echo $form->error($model, 'conversation_id'); ?>
                <div class="row lbs">
                    <?php echo $form->hiddenField($model, 'to', array('value' => $sendersIds)); ?>
                    <?php echo CHtml::hiddenField('recipients', $sendersLabels, array('id' => 'Contacts_labels_' . $counter)); ?>
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
                                <li><?php echo CHtml::link("<span class='icon-reply-multiple'></span>" . t('site', 'Rispondi a tutti: ') . "<span class='tags'>" . $sendersMultiple . "</span>", 'javascript:void(0)', array('class' => 'tags-btn', 'id' => 'sendersMultiple')); ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div id="str-labels-<?php echo $counter; ?>" class="usr-labels floatR"></div>
                    <ul id="contacts-lbs-<?php echo $counter; ?>" class="c-tags hidden"></ul>
                    <?php echo $form->error($model, 'to'); ?>
                </div>
                <div class="row">
                    <?php echo $form->textArea($model, 'body', array('class' => 'maibox-txt')); ?>
                    <?php echo $form->error($model, 'body'); ?>
                </div>
                <div class="row clearfix hidden">
                    <div class="reply-upload-block">
                        <?php
                        $uploadWidget = $this->widget('cms.extensions.xupload.XUpload', array(
                            'url' => Yii::app()->createUrl("/message/upload", array('counter' => $counter)),
                            //our XUploadForm
                            'model' => $files,
                            //We set this for the widget to be able to target our own form
                            'htmlOptions' => array('id' => 'message-form-' . $counter),
                            'attribute' => 'uploadimg-' . $counter,
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
                    <?php echo CHtml::link("<span class='icon-cancel'></span>", 'javascript:void(0)', array('class' => 'buttonS bDefault noLb floatR close-form')); ?>
                </div>
            </div>

            <?php $this->endWidget(); ?>
        </div>
    </div>
</li>