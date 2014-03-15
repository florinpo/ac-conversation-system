<?php

$m = Message::model()->findByPk(145);

$text = $m->text;

 $crc64= sprintf('%u', hash('crc32', $text)) . sprintf('%u', hash('crc32b', $text));
 
 var_dump($crc64);




/* * * $btn_grp1 ** */
$btn_grp1 = "<div class='btn-group floatL'>"; // start btn-group
$btn_grp1 .= "<ul class='toolbar'>";
$btn_grp1 .= "<li>" . CHtml::link("<span class='icon icon-box-add'></span><span>" . t('site', 'Archive') . "</span>", 'javascript:void(0)', array('class' => 'buttonM bDefault wLb mailbox-btn', 'id' => 'archive')) . "</li>";
$btn_grp1 .= "<li>" . CHtml::link("<span class='icon icon-remove'></span><span>" . t('site', 'Delete') . "</span>", 'javascript:void(0)', array('class' => 'buttonM bDefault wLb mailbox-btn', 'id' => 'delete')) . "</li>";
$btn_grp1 .= "<li>" . CHtml::link("<span class='icon icon-spam'></span><span>" . t('site', 'Spam') . "</span>", 'javascript:void(0)', array('class' => 'buttonM bDefault wLb mailbox-btn', 'id' => 'markspam')) . "</li>";
$btn_grp1 .= "</ul></div>"; // end btn-group

/* * * $btn_grp2 ** */
$btn_grp2 = "<div class='btn-group floatL'>"; // start btn-group
$btn_grp2 .= "<ul class='toolbar'>";
$btn_grp2 .= "<li>" . CHtml::link("<span class='icon icon-box-add'></span><span>" . t('site', 'Move to inbox') . "</span>", 'javascript:void(0)', array('class' => 'buttonM bDefault wLb mailbox-btn', 'id' => 'archive')) . "</li>";
$btn_grp2 .= "<li>" . CHtml::link("<span class='icon icon-remove'></span><span>" . t('site', 'Delete') . "</span>", 'javascript:void(0)', array('class' => 'buttonM bDefault wLb mailbox-btn', 'id' => 'delete')) . "</li>";
$btn_grp2 .= "<li>" . CHtml::link("<span class='icon icon-spam'></span><span>" . t('site', 'Spam') . "</span>", 'javascript:void(0)', array('class' => 'buttonM bDefault wLb mailbox-btn', 'id' => 'markspam')) . "</li>";
$btn_grp2 .= "</ul></div>"; // end btn-group



/* * * $btn_dropdown ** */
$btn_dropdown = "<div class='btn-group floatR'>"; // start dropdown
$btn_dropdown .= CHtml::link(t('site', 'More') . '<span class="caret"></span>', 'javascript:void(0)', array('class' => 'buttonM bDefault', 'data-toggle' => 'dropdown'));
$btn_dropdown .= "<ul class='dropdown-menu'>";
$btn_dropdown .= "<li>" . CHtml::link(t('site', 'Mark as read'), 'javascript:void(0)', array('id' => 'markread', 'class' => 'mailbox-btn')) . "</li>";
$btn_dropdown .= "<li>" . CHtml::link(t('site', 'Mark as unread'), 'javascript:void(0)', array('id' => 'markunread', 'class' => 'mailbox-btn')) . "</li>";
$btn_dropdown .= "<li>" . CHtml::link(t('site', 'Add flag'), 'javascript:void(0)', array('id' => 'addflag', 'class' => 'mailbox-btn')) . "</li>";
$btn_dropdown .= "<li>" . CHtml::link(t('site', 'Remove flag'), 'javascript:void(0)', array('id' => 'removeflag', 'class' => 'mailbox-btn')) . "</li>";
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
$btn_perm_delete = CHtml::link("<span class='icon-remove'></span><span>" . t('site', 'Delete permanently') . "</span>", 'javascript:void(0)', array('class' => 'buttonM bDefault single wLb mailbox-btn', 'id' => 'permanentdelete'));
/**
 * [actions wrapper]
 * */
$actions = "<div id='actions-mailbox' class='mailbox-actions-wrapper clearfix'>"; // start mailbox-actions-wrapper
$actions .= "<div class='actions-left'>"; // start actions-left
//$actions .= isset($_GET['folder']) && $_GET['folder'] == 'archived' ? $btn_inbox : '';
// we show the $btn_perm_delete only for spam and trash folders

$actions .= (isset($_GET['folder']) && $_GET['folder'] == 'spam') || (isset($_GET['folder']) && $_GET['folder'] == 'trash') ? $btn_perm_delete : '';
$actions .= isset($_GET['folder']) && $_GET['folder'] == 'trash' ? $btn_spam : '';
$actions .= isset($_GET['folder']) && $_GET['folder'] == 'spam' ? $btn_no_spam : '';
$actions .= (isset($_GET['folder']) && $_GET['folder'] == 'spam') || (isset($_GET['folder']) && $_GET['folder'] == 'trash') || (isset($_GET['folder']) && $_GET['folder'] == 'archived') ? '' : $btn_grp1;
$actions .= isset($_GET['folder']) && $_GET['folder'] == 'archived' ? $btn_grp2 : '';

$actions .= $btn_dropdown;
$actions .= "</div>"; // end actions-left
$actions .= "<div class='actions-right'>";
$actions .= "{summary}";
$actions .= "{pager}"; // pager
$actions .= "</div>";
$actions .= "</div>"; // end mailbox-actions-wrapper


/*
 * * [filters wrapper]
 */

/* * * $dropdown_filter ** */
$dropdown_filter = "<div class='btn-group floatR noBtn'>"; // start dropdown
$dropdown_filter .= CHtml::link("<span class='icon icon-reorder'></span>" . t('site', 'View') . '<span class="caret"></span>', 'javascript:void(0)', array('class' => 'trigger bLink', 'data-toggle' => 'dropdown'));
$dropdown_filter .= "<div class='menu-group dropdown-menu'>"; // menu group
$dropdown_filter .= "<div class='menu-title'>" . t('site', 'Ordinato per:') . "</div>"; // title
$dropdown_filter .= "<ul class='first'>";
$dropdown_filter .= "<li>" . CHtml::link("<span class='icon icon-ok'></span>" . t('site', 'data ascendente'), 'javascript:void(0)', array('id' => 'date-asc', 'class' => 'filter-link bLink date')) . "</li>";
$dropdown_filter .= "<li>" . CHtml::link("<span class='icon icon-ok'></span>" . t('site', 'data descendente'), 'javascript:void(0)', array('id' => 'date-desc', 'class' => 'filter-link bLink date checked')) . "</li>";
$dropdown_filter .= "</ul>";
$dropdown_filter .= "<div class='menu-title'>" . t('site', 'Filtri:') . "</div>"; // title
$dropdown_filter .= "<ul>";
$dropdown_filter .= "<li>" . CHtml::link("<span class='icon icon-ok'></span>" . t('site', 'tutti'), 'javascript:void(0)', array('id' => 'type-none', 'class' => 'filter-link bLink type checked')) . "</li>";
$dropdown_filter .= "<li>" . CHtml::link("<span class='icon icon-ok'></span>" . t('site', 'non letti'), 'javascript:void(0)', array('id' => 'type-unread', 'class' => 'filter-link bLink type')) . "</li>";
$dropdown_filter .= "<li>" . CHtml::link("<span class='icon icon-ok'></span>" . t('site', 'con bandierina'), 'javascript:void(0)', array('id' => 'type-flagged', 'class' => 'filter-link bLink type')) . "</li>";
$dropdown_filter .= "</ul>";
$dropdown_filter .= "</div>"; // end menu group
$dropdown_filter .= "</div>"; // end dropdown

$filters = "<div class='filters-wrapper clearfix'>"; // start filters-wrapper

$filters .= "<div class='mailbox-checkall-links'>"; // start mailbox-checkall-links
$filters .= "<span class='label'>" . t('site', 'Select:') . "</span>";
$filters .= CHtml::link(t('site', 'All'), 'javascript:void(0)', array('class' => 'checkall'));
$filters .= "<span class='vd-separator5'></span>";
$filters .= CHtml::link(t('site', 'None'), 'javascript:void(0)', array('class' => 'uncheckall inactive'));
$filters .= "</div>"; // end mailbox-checkall-links

$filters .= "<div class='sort-wrapper'>"; // start sort-wrapper
$filters .= $dropdown_filter;
$filters .= "</div>"; // end sort-wrapper 
$filters .= "</div>"; // end filters-wrapper

$loader = "<div class='loader-indicator loader-label-30'><span class='loader-txt'>" . t('site', 'Caricamento') . "<span></div>"
?>

<?php
//if ($this->beginCache('list-conversations', array(
//            'duration'=>3600 * 24 * 7,
//            'requestTypes'=>array('GET'),
//            'varyByParam' => array('folder', 'page'),
//            'dependency' => array(
//                'class' => 'system.caching.dependencies.CExpressionDependency',
//                'expression' => "Yii::app()->getGlobalState('Cache.gxc_mailbox_conversation')")))
//) {
    ?>

    <?php

    $this->widget('zii.widgets.CListView', array(
        'id' => 'mailbox',
        'dataProvider' => $data['dataProvider'],
        //'itemView' => 'common.blocks.messages._item_'.$folder,
        'itemView' => 'common.blocks.messages._item',
        'template' => $loader . $actions . $filters . '{items}' . $actions,
        'summaryText' => ' {start} - {end} ' . t('cms', 'from') . ' {count} ',
        'pager' => array(
            'class' => 'cms.extensions.customPagers.CustomPagerPNCounter',
            'counter' => false,
            'prevPageLabel' => "<span class='icon icon-arrow-left-narrow'></span>",
            'nextPageLabel' => "<span class='icon icon-arrow-right-narrow'></span>"
        ),
        'itemsTagName' => 'table',
        'loadingCssClass' => 'mailbox-loading',
        'ajaxUpdate' => true,
        'enablePagination' => true,
        'beforeAjaxUpdate' => 'function(){
        $(".loader-indicator").show();
     }',
        'afterAjaxUpdate' => '$.yiimailbox.updateMailbox',
        'itemsCssClass' => 'mailbox-items',
        'pagerCssClass' => 'pagination-pn pnDefault pnM floatR',
    ));
    ?>
    <?php //$this->endCache(); } ?>