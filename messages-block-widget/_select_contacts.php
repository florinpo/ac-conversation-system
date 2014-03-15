<div class="contact-list-mailbox clearfix">
    <?php if ($total > 0): ?>
        <div class="contact-list-wrapper">
            <?php
            /*             * * $btn_dropdown ** */
            $btn_dropdown = "<div class='btn-group floatR noBtn'>"; // start dropdown
            $btn_dropdown .= CHtml::link("<span class='icon icon-reorder'></span>" . t('site', 'Azioni') . '<span class="caret"></span>', 'javascript:void(0)', array('class' => 'trigger bLink', 'data-toggle' => 'dropdown'));
            $btn_dropdown .= "<ul class='dropdown-menu'>";
            $btn_dropdown .= "<li>" . CHtml::link("<span class='icon icon-ok'></span>" . t('site', 'Ordina discendente'), 'javascript:void(0)', array('id' => 'name-desc', 'class' => 'filter-link bLink')) . "</li>";
            $btn_dropdown .= "<li>" . CHtml::link("<span class='icon icon-ok'></span>" . t('site', 'Solo utenti con negozio'), 'javascript:void(0)', array('id' => 'has-shop', 'class' => 'filter-link bLink')) . "</li>";
            $btn_dropdown .= "<li class='hidden'>" . CHtml::link("<span class='icon icon-ok'></span>" . t('site', 'Solo utenti premium'), 'javascript:void(0)', array('id' => 'premium', 'class' => 'filter-link')) . "</li>";
            $btn_dropdown .= "</ul>";

            $btn_dropdown .= "</div>"; // end dropdown
            /**
             * [actions wrapper]
             * */
            $actions = "<div id='actions-contact-list' class='actions-wrapper clearfix'>"; // start mailbox-actions-wrapper
            if ($dataProvider->getItemCount() > 1):
                $actions .= "<input id='check-all' type='checkbox' />";
            endif;
            $actions .= $btn_dropdown;
            $actions .= "</div>";
            $loader = "<div class='loader-indicator loader-label-30'><span class='loader-txt'>" . t('site', 'Caricamento') . "<span></div>"
            ?>

            <?php
            $this->widget('zii.widgets.CListView', array(
                'id' => 'contact-list',
                'dataProvider' => $dataProvider,
                'itemView' => 'common.blocks.contact_list._item',
                'template' => $loader . $actions . '{items}',
                'summaryText' => false,
                'emptyText' => t('site', 'Nesun risultato trovato.'),
                'itemsTagName' => 'ul',
                'loadingCssClass' => 'contact-list-loading',
                'ajaxUpdate' => true,
                'enablePagination' => false,
                'beforeAjaxUpdate' => 'function(){
                                $(".loader-indicator").show();
                            }',
                'afterAjaxUpdate' => '$.yiicompose.updateCompose',
                'itemsCssClass' => 'contacts-items'
            ));
            ?>
        </div>
        <div class="selected-view-block">

            <div class="default-view">
                <div class="header">
                    <h2><?php echo t('site', 'You can select :count contacts', array(':count' => $total)) ?></h2>
                </div>
                <div class="content">
                    <p><?php echo t('site', 'To add recipients, click on the checkbox next to the person\'s name'); ?></p>
                </div>
            </div>
            
            <div class="selected-view hidden">
                <div class="header">
                    <h2><span class="counter"></span><?php echo t('site', 'contact(s) selected'); ?></h2>
                    <?php echo CHtml::link("<span class='icon icon-remove-sign'></span><span>" . t('site', 'Elimina tutti') . "</span>", 'javascript:void(0)', array('class' => 'sview-btn floatR', 'id' => 'delete-selected')) ?>
                </div>
                <div class="content">
                    <ul id="contacts-selected"></ul>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="empty">
            <?php echo t('site', 'No contact found. Please click here to learn how to add contacts...') ?>
        </div>
    <?php endif; ?>
    <div class="clear"></div>
</div>