<?php
$model = $data['model'];
$dataProvider = $data['dataProvider'];
$total = $data['total'];
$files = $data['files'];

// we clean the recipients session on page refresh
if (app()->user->hasState('recipients')) {
    app()->user->setState('recipients', null);
}
// we clean the images session
if (Yii::app()->user->hasState('imagesMailbox')) {
    app()->user->setState('imagesMailbox', null);
}



// $conv = Mailbox::conversation(51);
//        
//        $dependency = new CExpressionDependency('app()->params["lastModifications"]["gxc_mailbox_message"]');
//        $messages = $conv->cache(3600 * 24 * 7, $dependency, 2)->messages(array('scopes' => array('inbox' => array(user()->id, 'ASC'))));
//
//
//      foreach($messages as $m) {
//          echo $m->text;
//      }
        


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

<div class="form-wrapper mailbox-compose-block clearfix">
    <?php
    $form = $this->beginWidget('CActiveForm', array(
        'htmlOptions' => array('autocomplete' => 'off', 'enctype' => 'multipart/form-data', 'class' => 'compose-message-form'),
        'id' => 'message-form',
        'enableClientValidation' => false,
        'enableAjaxValidation' => true,
        'clientOptions' => array(
            'hideErrorMessage' => true,
            'validateOnSubmit' => true,
            'validateOnChange' => false,
            'validationUrl' => bu() . '/ajax', //Point to any ajax page you want
            )));
    ?>

    <?php echo $form->errorSummary($model, '', ''); ?>

    <div class="row clearfix lbs">

        <?php echo CHtml::hiddenField('recipients', '', array('id' => 'Contacts_labels')); ?>
        <?php echo $form->hiddenField($model, 'to', array()); ?>

        <label for="Contacts_labels"  class="labelTags floatL"><?php echo t('site', 'A'); ?></label>

        <div id="str-labels" class="usr-labels floatL"></div>
        <ul id="contacts-lbs" class="c-tags floatL hidden"></ul>


        <?php
        $this->beginWidget('zii.widgets.jui.CJuiDialog', array(
            'id' => 'select-contacts',
            // additional javascript options for the dialog plugin
            'options' => array(
                'resizable' => true,
                'autoOpen' => false,
                'modal' => true,
                'width' => 720,
                'height' => 490,
                'title' => t('site', 'Seleziona contatti'),
                'buttons' => array(
                    array(
                        'id' => 'btn-confirm',
                        'class' => 'ui-button-ok',
                        'text' => Yii::t('site', 'OK, aggiungi i selezionati'),
                        'click' => 'js:function() { 
                            $.yiicompose.appendTags();
                            $(this).dialog( "close" );
                         }',
                    ),
                    array(
                        'id' => 'btn-cancel',
                        'class' => 'ui-button-cancel',
                        'text' => Yii::t('site', 'Anula'),
                        'click' => 'js:function() { $(this).dialog( "close" );}',
                    )
                )
            ),
        ));

        $this->render('common.blocks.messages._select_contacts', array('dataProvider' => $dataProvider, 'total' => $total));

        $this->endWidget();

// the link that may open the dialog
        echo CHtml::link("<span class='icon icon-plus'></span>" . t('site', 'Select contacts'), '#', array(
            'onclick' => '$("#select-contacts").dialog("open"); return false;',
            'id' => 'selectContacts', 'class' => 'bLink floatR'
        ));
        ?>


        <span class="hidden"><?php echo $form->error($model, 'to'); ?></span>
        <div id="sids"></div>
    </div>

    <div class="row clearfix">
        <div class="labelIn">
            <label for="MessageForm_subject"><?php echo t('site', 'Oggeto'); ?></label>
            <?php echo $form->textField($model, 'subject', array("class" => "")); ?>
        </div>
        <span class="hidden"><?php echo $form->error($model, 'subject'); ?></span>
    </div>

    <div class="row clearfix">
        <div class="labelIn">
            <label for="MessageForm_body"><?php echo t('site', 'Inserici il messagio'); ?></label>
            <?php echo $form->textArea($model, 'body', array('class' => 'maibox-txt')); ?>
        </div>
        <span class="hidden"><?php echo $form->error($model, 'body'); ?></span>
    </div>
    <div class="row clearfix hidden">
        <div class="compose-upload-block">
            <?php
            $uploadWidget = $this->widget('cms.extensions.xupload.XUpload', array(
                'url' => Yii::app()->createUrl("/message/upload"),
                //our XUploadForm
                'model' => $files,
                //We set this for the widget to be able to target our own form
                'htmlOptions' => array('id' => 'message-form'),
                'attribute' => 'uploadimg',
                'multiple' => true,
                //Note that we are using a custom view for our widget
                //Thats becase the default widget includes the 'form' 
                //which we don't want here
                'formView' => 'common.blocks.messages._upload_form',
                'uploadView' => 'common.blocks.messages.upload_views.upload',
                'downloadView' => 'common.blocks.messages.upload_views.download',
                'options' => array(
                    'maxNumberOfFiles' => "js:$.yiicompose.maxNumberOfFiles",
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
        <?php echo CHtml::submitButton(t('site', 'Invia'), array('class' => 'buttonS bGreen floatL')); ?>
        <span class="vd-separator floatL"></span>
        <div class="fileinput-button">
            <?php echo CHtml::link("<span class='icon icon-camera'></span>" . t('site', 'Add images'), 'javascript:void(0)', array('id' => 'selectfile', 'class' => 'bLink t-right', 'title' => t('site', 'You can add 5 images. Max size 200 KB'))); ?>

            <?php
            if ($uploadWidget->hasModel()) :
                $uploadWidget->formOptions['accept'] = 'image/*';
                echo CHtml::activeFileField($uploadWidget->model, $uploadWidget->attribute, $uploadWidget->formOptions) . "\n";
            else :
                echo CHtml::fileField($name, $uploadWidget->value, $uploadWidget->formOptions) . "\n";
            endif;
            ?>
        </div>


        <?php echo CHtml::link("<span class='icon-undo'></span>", 'javascript:void(0)', array('id' => 'reset-form', 'class' => 'buttonS bDefault noLb floatR t-left', 'title' => t('site', 'Reset form'))); ?>
    </div>

    <?php $this->endWidget(); ?>
</div>
