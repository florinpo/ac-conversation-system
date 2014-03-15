<section id="ys-messages">
    <div class="box_round_c grid_19 omega">
        <h1><?php echo t('site', 'Messagi'); ?></h1>

        <?php
        
       //$table = Message::model()->tableSchema->name;
       //var_dump($table);
        
        //Yii::app()->setGlobalState('Cache.gxc_conversation_message', time());

//        $unreadInbox = Mailbox::countUnreadMsgs(user()->id, 'inbox');
//        $counterInbox = $unreadInbox > 0 ? "<span class='counter' id='counter-inbox'>".$unreadInbox."</span>" : "";
//        $unreadSpam = Mailbox::countUnreadMsgs(user()->id, 'spam');
//        $counterSpam = $unreadSpam > 0 ? "<span class='counter' id='counter-spammed'>".$unreadSpam."</span>" : "";
        $this->widget('zii.widgets.CMenu', array(
            'htmlOptions' => array('class' => 'tabnav floatL'),
            'encodeLabel' => false,
            'items' => array(
                array(
                    'label' => t('site', 'Inbox'),
                    'url' => array('page/render', 'slug' => 'messages', 'folder' => 'inbox'),
                    'active' => ($folder == 'inbox' && (!isset($_GET['action']) || $_GET['action'] != 'compose') ? true : false)
                ),
                array(
                    'label' => t('site', 'Sent'),
                    'url' => array('page/render', 'slug' => 'messages', 'folder' => 'sent'),
                    'active' => ($folder == 'sent' ? true : false)
                ),
                array(
                    'label' => t('site', 'Archived'),
                    'url' => array('page/render', 'slug' => 'messages', 'folder' => 'archived'),
                    'active' => ($folder == 'archived' ? true : false)
                ),
                array(
                    'label' => t('site', 'Spam'),
                    'url' => array('page/render', 'slug' => 'messages', 'folder' => 'spam'),
                    'active' => ($folder == 'spam' ? true : false)
                ),
                array(
                    'label' => t('site', 'Trash'),
                    'url' => array('page/render', 'slug' => 'messages', 'folder' => 'trash'),
                    'active' => ($folder == 'trash' ? true : false)
                )
            )
        ));
        ?>
        <?php
        $this->widget('zii.widgets.CMenu', array(
            'htmlOptions' => array('class' => 'tabnav floatR'),
            'encodeLabel' => false,
            'items' => array(
                array(
                    'label' => "<span class='icon icon-edit'></span>" . t('site', 'Compose'),
                    'url' => array('page/render', 'slug' => 'messages', 'action' => 'compose'),
                    'active' => (!isset($_GET['folder']) && (isset($_GET['action']) && $_GET['action'] == 'compose') ? true : false),
                ),
                array(
                    'label' => "<span class='icon icon-address-book'></span>" . t('site', 'Contact list'),
                    'url' => array('page/render', 'slug' => 'contact-list'),
                    'active' => ((isset($_GET['slug']) && $_GET['slug'] == 'contact-list') ? true : false),
                )
            )
        ));
        ?>
        <div class="clear"></div>
        <div class="tabnav-body mailbox-nav-body">
            <?php $this->render('cmswidgets.views.notification_frontend'); ?>
            <?php
            $this->render('common.blocks.messages._' . $view, array(
                'data' => $data,
                'folder' => $folder
            ));
            ?>
        </div>
</section>