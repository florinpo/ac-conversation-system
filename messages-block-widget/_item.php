<?php
$userid = user()->id;

$senders = Mailbox::conversationSenders($data->conversation_id, $userid, $this->folder, true, true); // senders id
$recipients = Mailbox::conversationRecipients($data->conversation_id, $userid, true); // recipients id

if ($this->folder == 'sent') {
    $participants = arr_strim($recipients, 34);
} else {
    $participants = arr_strim($senders, 34);
}



$cMessages = $data->messages(array('scopes' => array($this->folder => $userid)));


$counter = count($cMessages) > 1 ? '(' . count($cMessages) . ')' : '';
//$sender = CHtml::link($name, app()->createUrl('page/render', array('slug' => 'messages', 'folder' => 'inbox', 'action' => 'view', 'item-id' => $data->conversation_id)));


$date = itemsDate($data->modified);
$itemCssClass = $data->isNew($userid, $this->folder) ? 'msg-unread' : 'msg-read';
$itemCssClass .= $data->isFlagged($userid, $this->folder) ? ' flagged' : ' not-flagged';

$subject = str_trim($data->subject, 56);
$body = str_trim($data->text, 64);
?>
<tr class="mailbox-item <?php echo $itemCssClass; ?>">
<td class="gridCheckbox">
    <input class="checkbox" id="conv_<?php echo $data->conversation_id; ?>" type="checkbox" name="convs[]" value="<?php echo $data->conversation_id; ?>" />
</td>
<td class="grid_260">
    <div class="mailbox-participants">
       
        <?php echo CHtml::link($participants . ' ' . $counter, app()->createUrl('page/render', array('slug' => 'messages', 'folder' => $this->folder, 'action' => 'view', 'item-id' => $data->conversation_id))); ?>
    </div>
    <div class="mailbox-date"><?php echo $date; ?></div>
</td>


<td class="grid_390">
    <div class="mailbox-subject">
        <?php echo CHtml::link($subject, app()->createUrl('page/render', array('slug' => 'messages', 'folder' => $this->folder, 'action' => 'view', 'item-id' => $data->conversation_id))); ?>
        <?php if ($data->hasFile(user()->id, $this->folder)): ?>
            <span class="icon icon-attachment"></span>
        <?php endif; ?>
    </div>
    <div class="mailbox-content"><span><?php echo $body; ?></span></div>
</td>
<td class="gridCenter">
    <?php
    $mrclass = $data->isNew($userid, $this->folder) ? 'visible' : 'hidden';
    echo CHtml::link("<span class='icon icon-circle'></span>", 'javascript:void(0)', array('class' => 'mailbox-grid-btn mailbox-status ' . $mrclass, 'id' => 'markread'));
    ?>
    <?php
    $muclass = $data->isNew($userid, $this->folder) ? 'hidden' : 'visible';
    echo CHtml::link("<span class='icon icon-circle'></span>", 'javascript:void(0)', array('class' => 'mailbox-grid-btn mailbox-status ' . $muclass, 'id' => 'markunread'));
    ?>
    <?php
    $rfclass = $data->isFlagged($userid, $this->folder) ? 'visible' : 'hidden';
    echo CHtml::link("<span class='icon icon-flag'></span>", 'javascript:void(0)', array('class' => 'mailbox-grid-btn mailbox-flag ' . $rfclass, 'id' => 'removeflag'));
    ?>
    <?php
    $afclass = $data->isFlagged($userid, $this->folder) ? 'hidden' : 'visible';
    echo CHtml::link("<span class='icon icon-flag'></span>", 'javascript:void(0)', array('class' => 'mailbox-grid-btn mailbox-flag ' . $afclass, 'id' => 'addflag'));
    ?>

</td>
</tr>




