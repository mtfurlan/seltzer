<?php
/*
    Copyright 2014 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    storage.inc.php - Manage member storage assigments

    Seltzer is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    Seltzer is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Seltzer.  If not, see <http://www.gnu.org/licenses/>.
*/

// Installation functions //////////////////////////////////////////////////////

/**
 * @return This module's revision number.  Each new release should increment
 * this number.
 */
function storage_revision () {
    return 1;
}

/**
 * @return An array of the permissions provided by this module.
 */
function storage_permissions () {
    return array(
        'storage_view'
        , 'storage_edit'
        , 'storage_delete'
    );
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function storage_install($old_revision = 0) {
    if ($old_revision < 1) {
// create master list table
        $sql = 'CREATE TABLE IF NOT EXISTS `storage_plot` (
                    `pid` mediumint(8) unsigned NOT NULL,
                    `desc` varchar(255) NOT NULL,
                    `cid` varchar(255) NOT NULL,
                    `reapdate` date NOT NULL,
                    UNIQUE (`pid`),
                    PRIMARY KEY (`pid`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
                ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
// create storage log
        $sql = '
            CREATE TABLE IF NOT EXISTS `storage_log` (
                `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `user` varchar(255) NOT NULL,
                `action` varchar(255) NOT NULL,
                `pid` mediumint(8) unsigned NOT NULL,
                `desc` varchar(255) NOT NULL,
                `cid` varchar(255) NOT NULL,
                `reapdate` date NOT NULL,
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
                // Set default permissions
        $roles = array(
            '1' => 'authenticated'
            , '2' => 'member'
            , '3' => 'director'
            , '4' => 'president'
            , '5' => 'vp'
            , '6' => 'secretary'
            , '7' => 'treasurer'
            , '8' => 'webAdmin'
            , '9' => 'keymaster'
        );
        $default_perms = array(
            'director' => array('storage_view', 'storage_edit', 'storage_delete')
        );
        foreach ($roles as $rid => $role) {
            $esc_rid = mysql_real_escape_string($rid);
            if (array_key_exists($role, $default_perms)) {
                foreach ($default_perms[$role] as $perm) {
                    $esc_perm = mysql_real_escape_string($perm);
                    $sql = "INSERT INTO `role_permission` (`rid`, `permission`) VALUES ('$esc_rid', '$esc_perm')";
                    $sql .= " ON DUPLICATE KEY UPDATE rid=rid";
                    $res = mysql_query($sql);
                    if (!$res) die(mysql_error());
                }
            }
        }
    variable_set('storage_reap_months', '1:0/2:0/3:0/4:0/5:0/6:0/7:0/8:0/9:0/10:0/11:0/12:0');
    }
}

// Utility functions ///////////////////////////////////////////////////////////

// DB to Object mapping ////////////////////////////////////////////////////////

/**
 * Return data for one or more storage plots.
 *
 * @param $opts An associative array of options, possible keys are:
 *   'pid' If specified, returns a single plot with the matching plot id;
 *   'cid' If specified, returns all plots assigned to the contact with specified id;
 *   'reapafter' If specified, returns all plots reaped on or after this date;
 *   'reapbefore' If specified, returns all plots reaped on or before this date;
 * @return An array with each element representing a single plot assignment.
*/ 
function storage_data ($opts = array()) {
// Query database
    $sql = "
        SELECT
        pid
        , `desc`
        , cid
        , reapdate
        FROM storage_plot
        WHERE 1 ";
    if (!empty($opts['pid']) || $opts['pid'] != 0 ) {
        $esc_name = mysql_real_escape_string($opts['pid']);
        $sql .= " AND pid='" . $esc_name . "'";
    }
    if (!empty($opts['cid'])) {
        $esc_name = mysql_real_escape_string($opts['cid']);
        $sql .= " AND cid='" . $esc_name . "'";
    }
    if (!empty($opts['reapafter']) || (!empty($opts['reapbefore']))) {
        if(empty($opts['reapbefore'])) { $esc_reapbefore = mysql_real_escape_string(date('Y-m-d', strtotime('Dec 31'))); }
        if(empty($opts['reapafter'])) { $esc_reapafter = mysql_real_escape_string('0000-00-00'); }
        $sql .= " AND reapdate BETWEEN '" . $esc_reapbefore . "' AND '" . $esc_reapafter . "'";
    }

    $sql .= "ORDER BY pid ASC";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    // Store data
    $storage = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        // Contents of row are name, value
        $storage[] = $row;
        $row = mysql_fetch_assoc($res);
    }
    return $storage;
}

function storage_log_data ($opts = array()) {
// Query database
    $sql = "
        SELECT *
        FROM storage_log";
    if (!empty($opts['order'])) {
        if ($opts['order'] == "reverse") {
            $esc_order = mysql_real_escape_string("DESC");
        } else {
            $esc_order = mysql_real_escape_string("ASC");
        }
        $sql .= " ORDER BY timestamp " . $esc_order;
    }
    if (!empty($opts['count']))  {
        $esc_count = mysql_real_escape_string($opts['count']);
        $sql .= " LIMIT ".$esc_count;
    }
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    // Store data
    $log = array();
    $row = mysql_fetch_assoc($res);
    while (!empty($row)) {
        // Contents of row are name, value
        $log[] = $row;
        $row = mysql_fetch_assoc($res);
    }
    return $log;
}


/**
 * Add a storage plot 
 * @param $plot The plots id,description,reapdate
 * @return The plot structure with as it now exists in the database.
 */
function storage_add ($plot) {
    // Escape values
    $fields = array('pid', 'desc', 'reapdate');
    if (isset($plot['pid'])) {
        // Add key if nonexists, otherise Update existing key
        $pid = $plot['pid'];
        $esc_pid = mysql_real_escape_string($plot['pid']);
        $esc_desc = mysql_real_escape_string($plot['desc']);
        $esc_reapdate = mysql_real_escape_string($plot['reapdate']);
        $sql = "INSERT INTO storage_plot (pid, `desc`, reapdate) ";
        $sql .="VALUES ('" . $esc_pid . "', '" . $esc_desc . "', '" . $esc_reapdate . "') ";
        $res = mysql_query($sql);
        if (!$res) {
            message_register('ERROR: ' . mysql_error());
        } else {
            storage_log($plot);
            message_register('Storage Plot Added');
        }
        return crm_get_one('storage', array('pid'=>$pid));
    }
}

/**
 * Update an existing plot 
 * @param $opts The plot's id,description,occupant,reapdate
 * @return The plot structure as it now exists in the database.
 */
function storage_edit ($opts) {

    if (isset($opts['pid'])) {
        // Get current info
        $plot = crm_get_one('storage', array('pid'=>$opts['pid']));
        // Add key if nonexists, otherise Update existing key
        $pid = $opts['pid'];
        $esc_pid = mysql_real_escape_string($opts['pid']);
        $sql = "UPDATE storage_plot ";
        $sql .= "SET ";
        if (isset($opts['desc'])) {
            $esc_desc = mysql_real_escape_string($opts['desc']);
            $sql .="`desc` = '" . $esc_desc . "' ,";
        }
        if (isset($opts['cid'])) {
            $esc_cid = mysql_real_escape_string($opts['cid']);
            $sql .="cid = '" . $esc_cid . "' ,";
        }
        if (isset($opts['reapdate'])) {
            $esc_reapdate = mysql_real_escape_string($opts['reapdate']);
            $sql .="reapdate = '" . $esc_reapdate . "'";
        }
        $sql .= "WHERE pid = '" . $esc_pid . "' ";
        $res = mysql_query($sql);
        // if (!$res) die(mysql_error());
        // message_register('Secret updated');
        if (!$res) {
           message_register('SQL: ' . $sql . '<br>ERROR: ' . mysql_error());
        } else {
            storage_log($opts);
            if (!isset($opts['reapdate'])) { message_register('Storage Plot updated'); } // multiple calls for reaping, skip notice
        }
        return crm_get_one('storage', array('pid'=>$pid));
    }
}

/**
 * Delete an existing plot 
 * @param $opts the pid of the plot to delete
 */
function storage_delete ($opts) {
    if (isset($opts['pid'])) {
        $plot = crm_get_one('storage', array('pid'=>$opts['pid']));
        $plot['action'] = 'Delete';
        $esc_name = mysql_real_escape_string($opts['pid']);
        $sql = "DELETE FROM storage_plot WHERE pid = '" . $esc_name . "'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        if (mysql_affected_rows() > 0) {
            storage_log($plot);
            message_register('Storage Plot '.$esc_name.' deleted.');
        }
    } else {
        message_register('No such Storage Plot');
    }
}

function user_plot_vacate ($opts) {
if (isset($opts['pid'])) {
        $plot = crm_get_one('storage', array('pid'=>$opts['pid']));
        $esc_name = mysql_real_escape_string($opts['pid']);
        $sql = "UPDATE storage_plot SET cid = 0 WHERE pid = '" . $esc_name . "'";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        if (mysql_affected_rows() > 0) {
            storage_log($opts);
            message_register('Storage Plot '.$esc_name.' vacated.');
        }
    } else {
        message_register('No such Storage Plot');
    }
}

function storage_log ($opts) {

    if (isset($opts['pid'])) {
        // Add to logfile
        $myid = user_id();
        $esc_myid = mysql_real_escape_string($myid);
        $esc_action = mysql_real_escape_string($opts['action']);
        $esc_pid = mysql_real_escape_string($opts['pid']);
        $plot = crm_get_one('storage', array('pid'=>$opts['pid']));
        if (!empty($opts['desc'])) {
            $esc_desc = mysql_real_escape_string($opts['desc']);
        } else {
            $esc_desc = mysql_real_escape_string($plot['desc']);
        }
        if (!empty($opts['cid'])) {
            $esc_cid = mysql_real_escape_string($opts['cid']);
        } else {
            $esc_cid = mysql_real_escape_string($plot['cid']);
        }
        if (isset($opts['reapdate'])) {
            $esc_reapdate = mysql_real_escape_string($opts['reapdate']);
        } else {
            $esc_reapdate = mysql_real_escape_string($plot['reapdate']);
        }

        $sql = "INSERT INTO storage_log (user, action, pid, `desc`, cid, reapdate) ";
        $sql .= "VALUES (".$esc_myid.",'".$esc_action."',".$esc_pid.",'".$esc_desc."','".$esc_cid."','".$esc_reapdate."');";
        $res = mysql_query($sql);
        // if (!$res) die(mysql_error());
        // message_register('Secret updated');
        if (!$res) {
            message_register('SQL: ' . $sql . '<br>ERROR: ' . mysql_error());
        // } else {
        //     message_register('Storage Log updated');
        }
    }
}

function storage_reap ($opts) {
        foreach (explode(",", $opts['pidsToReap']) as $pid) { $plots[] = array('pid' => $pid); }
        $today = date('Y-m-d');
        foreach ($plots as $plot) {
            $plotinfo = crm_get_one('storage', array('pid'=>$plot['pid']));
            $contact = crm_get_one('contact', array('cid'=>$plotinfo['cid']));
            if (!empty($contact)) { $contact_email[] = $contact['email']; }
            
            if ($_SESSION['reap_filter_option'] == 'weekThree') {
                storage_edit(array('pid'=>$plot['pid'],'reapdate'=>$today));
            }
        }
        if (!empty($contact_email)) {
            $to = implode(",", $contact_email);
            $subject = $opts['subject'];
            $message = $opts['content'];
            $fromheader = "From: \"i3Detroit CRM\" <crm@i3detroit.org>\r\n";
            $contentheader = "Content-Type: text/html; charset=ISO-8859-1\r\n";
            $ccheader = "Cc: ".variable_get('storage_admins','')."\r\n";
            $headers = $fromheader.$contentheader.$ccheader;
            message_register("Sending email:");
            message_register("To:".$to);
            message_register("Subject:".$subject);
            message_register("Message:".$message);
            message_register("Headers:".$headers);
            if(mail($to, $subject, $message, $headers)) {
                message_register("email sent successfully");
            } else {
                message_register("email failure");
            }
            // -announce email
            $to = "i3-annouce@groups.google.com";
            $subject = $opts['subject_announce'];
            $message = $opts['content_announce'];
            $fromheader = "From: \"i3Detroit CRM\" <crm@i3detroit.org>\r\n";
            $contentheader = "Content-Type: text/html; charset=ISO-8859-1\r\n";
            $ccheader = "Cc: ".variable_get('storage_admins','')."\r\n";
            $headers = $fromheader.$contentheader.$ccheader;
            message_register("Sending email:");
            message_register("To:".$to);
            message_register("Subject:".$subject);
            message_register("Message:".$message);
            message_register("Headers:".$headers);
            if(mail($to, $subject, $message, $headers)) {
                message_register("email sent successfully");
            } else {
                message_register("email failure");
            }

        } else {
            error_register("No emails found for selected plots, nothing sent.");
        }
}

/**
 * @return Array mapping payment method values to descriptions.
 */
function reaping_options () {
    $options = array();
    $options['first'] = 'Initial Notification (Monday before 1st Tuesday)';
    $options['second'] = 'Reminder Notification (2nd Tuesday)';
    $options['third'] = 'Reap plots (3rd Tuesday)';
    $options['fourth'] = 'Plot return (4th Tuesday)';
    return $options;
}

function storage_reap_config ($opts) {
    // message_register(var_export($opts,true));
    
    switch ($opts['action']) {
        // Plot management
        case 'Update Months':
            $newMonths = 0;
            for ($m=1; $m<12; $m++) {
                if (array_key_exists($m,$opts['reap'])) {
                    $newMonths = $newMonths + (pow(2,$m-1));
                }
            }
            variable_set('storage_reap_months',$newMonths);
            message_register('Reaping months updated');
            message_register(decbin(var_export($newMonths,true)));
        break;
        
        case 'Update Subject':
            break;
        
        case 'Update Body':
            break;
            
        case 'Update Announce Subject':
            break;
            
        case 'Update Announce Body':
            break;
        
        case 'Update Storage Admins':
            break;
    }
}
// Tables //////////////////////////////////////////////////////////////////////
// Put table generators here
function storage_table () {
    // Determine settings
    $export = false;
    // foreach ($opts as $option => $value) {
    //     switch ($option) {
    //         case 'export':
    //             $export = $value;
    //             break;
    //     }
    // }
    // Get storage data
    $storage = crm_get_data('storage');
    if (count($storage) < 1) {
        return array();
    }

    // Initialize table
    $table = array(
        'columns' => array(
            array('title' => 'Plot#')
            , array('title' => 'Description')
            , array('title' => 'Contact')
            , array('title' => 'Last Reaping')
        )
        , 'rows' => array()
    );

    if (user_access('storage_edit') || user_access('storage_delete')) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
    // Add rows
    $contact_data = crm_get_data('contact', $contact_opts);
    foreach ($storage as $plot) {
        // Add storage data
        $row = array();
        if (user_access('storage_view')) {
            // Add cells
            $row[] = $plot['pid'];
            $row[] = $plot['desc'];
            if ( $plot['cid'] ) {
                $crm_user = crm_get_one('contact',array('cid'=>$plot['cid']));
                if ($crm_user) {
                    $cid_to_contact = crm_map($contact_data, 'cid');
                    $row[] = theme('contact_name', $cid_to_contact[$plot['cid']], !$export);
                } else {
                    $row[] = $plot['cid'];
                }
            } else {
                $row[] = '';
            }
            $row[] = $plot['reapdate'];
       }
        // Construct ops array
        $ops = array();
        // Add edit op
        if (user_access('storage_edit')) {
            $ops[] = '<a href=' . crm_url('storage_edit&pid=' . $plot['pid']) . '>edit</a> ';
            // $ops[] = '<a href=' . crm_url('storage_edit&name=' . $key['pid'] . '#tab-edit') . '>edit</a> ';
        }
        // Add delete op
        if (user_access('storage_delete')) {
            $ops[] = '<a href=' . crm_url('delete&type=storage&id=' . $plot['pid']) . '>delete</a>';
        }
        // Add ops row
        $row[] = join(' ', $ops);
        $table['rows'][] = $row;  
    }
  return $table;
}

function storage_log_table ($opts) {
    // Determine settings
    $export = false;
    // foreach ($opts as $option => $value) {
    //     switch ($option) {
    //         case 'export':
    //             $export = $value;
    //             break;
    //     }
    // }
    // Get storage data
    $storage_log = crm_get_data('storage_log', $opts);
    if (count($storage_log) < 1) {
        return array();
    }

    // Initialize table
    $table = array(
        'columns' => array(
            array('title' => 'Timestamp')
            , array('title' => 'Action')
            , array('title' => 'User')
            , array('title' => 'Plot#')
            , array('title' => 'Description')
            , array('title' => 'Contact')
            , array('title' => 'Last Reaping')
        )
        , 'rows' => array()
    );

   // Add rows
    $contact_data = crm_get_data('contact', $contact_opts);
    foreach ($storage_log as $log) {
        // Add storage data
        $row = array();
        if (user_access('storage_view')) {
            // Add cells
            $row[] = $log['timestamp'];
            $row[] = $log['action'];
            $log_user = crm_get_one('contact',array('cid'=>$log['user']));
            $user_to_contact = crm_map($contact_data, 'cid');
            $row[] = theme('contact_name', $user_to_contact[$log['user']], !$export);
            $row[] = $log['pid'];
            $row[] = $log['desc'];
            if ( $log['cid'] ) {
                $crm_user = crm_get_one('contact',array('cid'=>$log['cid']));
                if ($crm_user) {
                    $cid_to_contact = crm_map($contact_data, 'cid');
                    $row[] = theme('contact_name', $cid_to_contact[$log['cid']], !$export);
                } else {
                    $row[] = $log['cid'];
                }
            } else {
                $row[] = '';
            }
            $row[] = $log['reapdate'];
        }
    $table['rows'][] = $row;  
    }
  return $table;
}

function storage_plot_table ($opts) {
    // Determine settings
    $export = false;
    // foreach ($opts as $option => $value) {
    //     switch ($option) {
    //         case 'export':
    //             $export = $value;
    //             break;
    //     }
    // }
    // Get storage data
    $cid = $opts['cid'];
    $data = crm_get_data('storage', array('cid'=>$cid));
    if (count($data) < 1) {
        $data['cid'] = $opts['cid'];
    }

    // Initialize table

    $table = array(
        "id" => '',
        "class" => '',
        "rows" => array(),
        "columns" => array()
    );

    // Add columns
    if (user_access('storage_view') || $opts['cid'] == user_id()) {
        $table['columns'][] = array("title"=>'Name', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'Plot#', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'Description', 'class'=>'', 'id'=>'');
        $table['columns'][] = array("title"=>'Last Reaping', 'class'=>'', 'id'=>'');
    }

    // Add ops column
    if (!$export && user_access('storage_edit') || user_access('storage_delete') || $opts['cid'] == user_id()) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }

    // Add rows
    foreach ($data as $plot) {
        // Add storage data
        $row = array();
        if (user_access('storage_view') || $opts['cid'] == user_id()) {
            // Add cells
            $row[] = theme('contact_name', $opts['cid'], !$export);
            if ( isset($plot['pid'])) {
                $row[] = $plot['pid'];
                $row[] = $plot['desc'];
                $row[] = $plot['reapdate'];
            } else {
                $row[] = '';
                $row[] = '';
                $row[] = '';
            }
        }
        // Construct ops array
        $ops = array();
        if (user_access('storage_edit') || $opts['cid'] == user_id()) {
            // if plot already assigned, show "vacate" button
            if (isset($plot['pid'])) {
                    $ops[] = '<a href=' . crm_url('plot_vacate&pid=' . $plot['pid'].'&cid=' . $opts['cid']) . '>Vacate</a> ';
            } else {
                    $ops[] = '<a href=' . crm_url('plot_assign&cid=' . $opts['cid']) . '>Assign</a> ';
            }
        }
        // Add ops row
        $row[] = join(' ', $ops);
        $table['rows'][] = $row;  
    }
  return $table;
}

function storage_reap_table () {
    // Determine settings
    $export = false;
    // foreach ($opts as $option => $value) {
    //     switch ($option) {
    //         case 'export':
    //             $export = $value;
    //             break;
    //     }
    // }

    // Get data
    // if today is before the 4th Thursday, show this month, otherwise show next month
    $fourthThu = date('d', strtotime('fourth thursday of this month'));
    if (date('d') > $fourthThu) {
        $fromdate = date('Y-m-d', strtotime('fourth tuesday of next month'));
        $month = date('m', strtotime('next month'));
        $monthName = date('F', strtotime('next month'));
    } else {
        $fromdate = date('Y-m-d', strtotime('fourth tuesday of this month'));
        $month = date('m');
        $monthName = date('F');
    }

    $storage = crm_get_data('storage', array('reapsince'=>$fromdate));
    if (count($storage) < 1) {
        return array();
    }
    $remainingMonths = 12 - $month;
    $numToReap = (count($storage)/$remainingMonths);
    $storage = array_slice($storage, 0, $numToReap - 1);

    // Initialize table
    $table = array(
        'columns' => array(
            array('title' => 'Plot#')
            , array('title' => 'Description')
            , array('title' => 'Contact')
            , array('title' => 'Last Reaping')
        )
        , 'rows' => array()
    );

    $contact_data = crm_get_data('contact', $contact_opts);
    $toReap = array();
    foreach ($storage as $plot) {
        // var_dump_pre($plot);
        $row = array();
        $row[] = $plot['pid'];
        $toReap[] = $plot['pid'];
        $row[] = $plot['desc'];
        if ( $plot['cid'] ) {
            $crm_user = crm_get_one('contact',array('cid'=>$plot['cid']));
            if ($crm_user) {
                $cid_to_contact = crm_map($contact_data, 'cid');
                $contact = theme('contact_name', $cid_to_contact[$plot['cid']], !$export);
            } else {
                $contact = $plot['cid'];
            }
        } else {
            $contact= "";
        }
        $row[] = $contact;
        $row[] = $plot['reapdate'];
        // }
        $rows[] = $row;
        $table['rows'][] = $row;  
    }
    $_SESSION['pids_to_reap'] = $toReap;
    $_SESSION['reap_month'] = $monthName;
    return $table;
}
// Forms ///////////////////////////////////////////////////////////////////////
// Put form generators here

function storage_add_form () {
    
    // Ensure user is allowed to edit keys
    if (!user_access('storage_edit')) {
        return NULL;
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'storage_add',
        'hidden' => array(
            'action' => 'Add'
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Add Storage Plot',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'Plot#',
                        'name' => 'pid'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Description',
                        'name' => 'desc'
                    ),
                    array(
                       'type' => 'text'
                        , 'label' => 'Last Reaping'
                        , 'name' => 'reapdate'
                        , 'value' => date('Y-m-d')
                        , 'class' => 'date float'
                    ),
                    array(
                        'type' => 'submit',
                        'value' => 'Add'
                    )
                )
            )
        )
    );
    return $form;
}

function storage_edit_form ($name) {
    // Ensure user is allowed to edit keys
    if (!user_access('storage_edit')) {
        return NULL;
    }

    // Get secret
    $data = crm_get_one('storage', array('pid'=>$name));
    if (empty($data['pid']) || count($data['pid']) < 1) {
        return array();
    }

    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'storage_edit',
        'hidden' => array(
            'action' => 'Edit'
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Edit Storage Plot',
                'fields' => array(
                    array(
                        'type' => 'readonly',
                        'label' => 'Plot#',
                        'name' => 'pid',
                        'value' => $data['pid']
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Description',
                        'name' => 'desc',
                        'value' => $data['desc'],
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Contact',
                        'name' => 'cid',
                        'value' => $data['cid'],
                    ),
                    array(
                       'type' => 'text'
                        , 'label' => 'Last Reaping'
                        , 'name' => 'reapdate'
                        , 'value' => date('Y-m-d')
                        , 'class' => 'date float'
                    ),
                    array(
                        'type' => 'submit',
                        'desc' => 'Save'
                    )
                )
            )
        )
    );
    
    return $form;
}

function storage_reap_filter_form () {
    // Available filters
    $filters = array(
        'weekOne' => 'Initial Notification (Monday before 1st Tuesday)'
        , 'weekTwo' => 'Reminder Notification (2nd Tuesday)'
        , 'weekThree' => 'Reap plots (3rd Tuesday)'
        , 'weekFour' => 'Plot return (4th Tuesday)'
    );
    
    // Default filter
    $selected = empty($_SESSION['reap_filter_option']) ? 'weekOne' : $_SESSION['reap_filter_option'];
    
    // Construct hidden fields to pass GET params
    $hidden = array();
    foreach ($_GET as $key=>$val) {
        $hidden[$key] = $val;
    }
    $form = array(
        'type' => 'form'
        , 'method' => 'get'
        , 'command' => 'reap_filter'
        , 'hidden' => $hidden,
        'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Storage Reaping for month of '.$_SESSION['reap_month']
                ,'fields' => array(
                    array(
                        'type' => 'select'
                        , 'name' => 'filter'
                        , 'options' => $filters
                        , 'selected' => $selected
                    ),
                    array(
                        'type' => 'submit'
                        , 'value' => 'Select'
                    )
                )
            )
        )
    );
    return $form;
}

function storage_reap_email_form() {
    $pidsToReap = join(",", $_SESSION['pids_to_reap']);
    $emailText['weekOne']['subject'] = $_SESSION['reap_month']." Storage Reaping: First Notice";
    $emailText['weekOne']['content']= "(One) Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
    $emailText['weekOne']['subject_announce']= "(One) ".$_SESSION['reap_month']." Storage Cleaning - First Notice";
    $emailText['weekOne']['content_announce']= "(One) Plots[$pidsToReap] - i3-announce message body";

    $emailText['weekTwo']['subject'] = $_SESSION['reap_month']." Storage Reaping: Second Notice";
    $emailText['weekTwo']['content'] = "(Two) Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?";
    $emailText['weekTwo']['subject_announce']= $_SESSION['reap_month']." Storage Cleaning - Second Notice";
    $emailText['weekTwo']['content_announce']= "(Two) Plots[$pidsToReap] - i3-announce message body";

    $emailText['weekThree']['subject'] = $_SESSION['reap_month']." Storage Reaping: Third Notice";
    $emailText['weekThree']['content'] = "(Three) At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat.";
    $emailText['weekThree']['subject_announce']= $_SESSION['reap_month']." Storage Cleaning - Third Notice";
    $emailText['weekThree']['content_announce']= "(Three) Plots[$pidsToReap] - i3-announce message body";

    $emailText['weekFour']['subject'] = $_SESSION['reap_month']." Storage Reaping: Fourth Notice";
    $emailText['weekFour']['content'] = "(Four) Bacon ipsum dolor amet swine sint andouille velit, tongue voluptate eu labore. Meatball tail shoulder irure. Deserunt kevin shoulder hamburger incididunt in jowl strip steak. Reprehenderit turkey hamburger non incididunt. Reprehenderit ipsum fugiat sausage rump corned beef ex beef ribs. Bacon dolore irure ullamco frankfurter short loin ut sed consequat. Non voluptate laboris, ground round fugiat do shoulder ham hock excepteur swine consequat beef pastrami.";
    $emailText['weekFour']['subject_announce']= $_SESSION['reap_month']." (Four) Storage Cleaning - Fourth Notice";
    $emailText['weekFour']['content_announce']= "(Four) Plots[$pidsToReap] - i3-announce message body";
    
    $thisWeek = empty($_SESSION['reap_filter_option']) ? 'weekOne' : $_SESSION['reap_filter_option'];
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'storage_reap'
        , 'label' => 'Email To Send'
        , 'hidden' => array(
            'action' => 'Reap'
            , 'pidsToReap' => $pidsToReap
        )
        , 'fields' => array(
            array(
                'type' => 'textarea'
                , 'label' => 'Subject - contacts'
                , 'name' => 'subject'
                , 'value' => $emailText[$thisWeek]['subject']
                , 'cols' => '100'
                , 'rows' => '1'
            )
            , array(
                'type' => 'textarea'
                , 'label' => 'Message Body - contacts'
                , 'name' => 'content'
                , 'value' => $emailText[$thisWeek]['content']
                , 'cols' => '100'
                , 'rows' => '20'
            )
            , array(
                'type' => 'textarea'
                , 'label' => 'Subject - Announce'
                , 'name' => 'subject_announce'
                , 'value' => $emailText[$thisWeek]['subject_announce']
                , 'cols' => '100'
                , 'rows' => '1'
            )
            , array(
                'type' => 'textarea'
                , 'label' => 'Message Body - Announce'
                , 'name' => 'content_announce'
                , 'value' => $emailText[$thisWeek]['content_announce']
                , 'cols' => '100'
                , 'rows' => '20'
            )
            , array(
                'type' => 'submit',
                'value' => 'REAP!'
            )
        )
    );
    return $form;
}

function storage_delete_form ($plot) {
    // Ensure user is allowed to delete keys
    if (!user_access('storage_delete')) {
        return NULL;
    }
    
    // Get secret data
    $storage = crm_get_one('storage', array('pid'=>$plot));
    // Construct secret name
    $pid = $storage['pid'];
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'storage_delete',
        'submit' => 'Delete',
        'hidden' => array(
            'pid' => $pid,
            'action' => 'Delete'
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Delete Storage Plot',
                'fields' => array(
                    array(
                        'type' => 'message',
                        'value' => '<p>Are you sure you want to delete Storage Plot ' . $pid . '? This cannot be undone.',
                    // ),
                    // array(
                    //     'type' => 'submit',
                    //     'desc' => 'Delete'
                    )
                )
            )
        )
    );
    return $form;
}

function user_plot_assign_form ($opts) {
    // Get plot
    $data = crm_get_data('storage', array('pid'=>$opts['pid']));
    // if (!empty($data['pid']) ) {
    //      $openplots[] = $data['pid'];
    // }
    $esc_cid = mysql_real_escape_string($opts['cid']);
    // Get available plots
    $sql = "SELECT pid, `desc` from storage_plot ";
    $sql .= "WHERE (cid = '')";
    $sql .= "ORDER by pid;";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    while($rs=mysql_fetch_array($res)){
        $openplots[$rs['pid']] = $rs['pid'] ." - ". $rs['desc'];
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'user_plot_assign',
        'hidden' => array(
            'cid' => $opts['cid'],
            'action' => 'Assign'
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Assign Storage Plot',
                'fields' => array(
                array(
                        'type' => 'select',
                        'label' => 'Plot',
                        'name' => 'pid',
                        'options' => $openplots
                    ),
                    array(
                        'type' => 'submit',
                        'desc' => 'Assign'
                    )
                )
            )
        )
    );
    return $form;
}

function user_plot_vacate_form ($opts) {

    if (!user_access('storage_edit') && $opts['cid'] != user_id()) {
        return NULL;
    }

    // Get plot
    $data = crm_get_data('storage', array('pid' => $opts['pid']));
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'user_plot_vacate',
        'submit' => 'Vacate',
        'hidden' => array(
            'pid' => $opts['pid'],
            'cid' => $opts['cid'],
            'desc' => $opts['desc'],
            'reapdate' => $opts['reapdate'],
            'action' => 'Vacate'
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Vacate Storage Plot',
                'fields' => array(
                    array(
                        'type' => 'message',
                        'value' => '<p>Are you sure you want to vacate Storage Plot ' . $opts['pid'] . '? This cannot be undone.',
                    // ),
                    // array(
                    //     'type' => 'submit',
                    //     'desc' => 'Delete'
                    )
                )
            )
        )
    );
    return $form;
}

function storage_reap_config_form () {
    // Ensure user is allowed to edit keys
    if (!user_access('storage_edit')) {
        return NULL;
    }
    
    // // // 
    // Storage Reap Months
    // // //

    $storage_reap_months = variable_get('storage_reap_months','4095'); // store as binary bitmasks
    // Form table rows and columns
    $columns = array();
    $rows = array();

    // Add column titles
    $columns[] = array('title' => 'Month');
    $columns[] = array('title' => 'Month');
    $columns[] = array('title' => 'Reaping');
    
    // Process rows
    $reap = array();
    for ($m = 1; $m <= 12; $m++) {

        $row = array();
        $row[] = array(
            'type' => 'message'
            , 'value' => $m
        );
        $row[] = array(
            'type' => 'message'
            , 'value' => date('F', mktime(0, 0, 0, $m, 10))
        );
        // check bitwise for month to see if set
        ((pow(2,$m-1)) & $storage_reap_months) ? $checked = true : $checked = false;
        $row[] = array(
            'type' => 'checkbox',
            'name' => "reap[$m]",
            'checked' => $checked
        );
        $rows[] = $row;
    }

    // Create form structure
     $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'storage_reap_config'
        , 'fields' => array(
            array(
                'type' => 'table'
                , 'columns' => $columns
                , 'rows' => $rows
            )
            , array(
                'type' => 'submit',
                'name' => 'action',
                'value' => 'Update Months'
            )
        )
    );

    // // //
    // Member Email Subject
    // // //
    
    // // //
    // Member email body
    // // //

    // // //
    // Announce email subject
    // // //

    // // //
    // Announce email body
    // // //

    // // //
    // Storage Admin Emails
    // // //

    return $form;
}

// Themeing ////////////////////////////////////////////////////////////////////

function theme_storage_add_form ($name) {
    return theme('form', crm_get_form('storage_add', $name));
}

function theme_storage_edit_form ($name) {
    return theme('form', crm_get_form('storage_edit', $name));
}

function theme_user_plot_assign_form ($cid) {
    return theme('form', crm_get_form('user_plot_assign', $cid));
}

function theme_user_plot_vacate_form ($opts) {
    return theme('form', crm_get_form('user_plot_vacate', $opts));
}

function theme_storage_reap_form ($opts) {
    return theme('form', crm_get_form('storage_reap'), $opts);
}

function theme_storage_reap_email_form ($opts) {
    return theme('form', crm_get_form('storage_reap_email'), $opts);
}

function theme_storage_reap_filter_form ($opts) {
    return theme('form', crm_get_form('storage_reap_filter'), $opts);
}

function theme_storage_reap_config_form ($opts) {
    return theme('form', crm_get_form('storage_reap_config'), $opts);
}

// Pages ///////////////////////////////////////////////////////////////////////

/**
 * @return An array of pages provided by this module.
 */
function storage_page_list () {
    $pages = array();
    if (user_access('storage_view')) {
        $pages[] = 'storage';
        $pages[] = 'storage_edit';
    }
    return $pages;
}

/**
 * Page hook.  Adds module content to a page before it is rendered.
 *
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
 * @param $options The array of options passed to theme('page').
*/
function storage_page (&$page_data, $page_name, $options) {
    switch ($page_name) {
        // Plot management
        case 'storage':
            page_set_title($page_data, 'Storage Plots');
            
            if (user_access('storage_view')) {
                $storage = theme('table', 'storage', array('show_export'=>true));
            }
            if (user_access('storage_edit')) {
                $storage .= theme('storage_add_form', '');
            }
            page_add_content_top($page_data, $storage, 'View' );
            
            // Reap tab
            if (user_access('storage_edit')) {
                $thisWeek = array_key_exists('reap_filter', $_SESSION) ? $_SESSION['reap_filter'] : array('week'=>'weekOne');
                // $reap_content = theme('form', storage_reap_form());
                $reap_content = theme('table', 'storage_reap', array('show_export'=>true));
                $reap_content .= theme('form', crm_get_form('storage_reap_filter'));
                $reap_content .= theme('form', crm_get_form('storage_reap_email'));
                page_add_content_top($page_data, $reap_content, 'Reap');
            }
            
            // Config tab
            if (user_access('storage_edit')) {
                $config_content = theme('form', crm_get_form('storage_reap_config'));
                page_add_content_top($page_data, $config_content, 'Config');
            }
 
            if (user_access('storage_view')) {
                $storage_log = theme('table', 'storage_log', array('count'=>15, 'order'=>'reverse'));
            }
            page_add_content_top($page_data, $storage_log, 'Log' );
            
            break;

        case 'storage_edit':
            // Capture secret to edit
            $pid = $options['pid'];
            if (empty($pid)) {
                return;
            }           
            page_set_title($page_data, 'Edit a Storage Plot');
            if (user_access('storage_edit')) {
                page_add_content_top($page_data, theme('storage_edit_form', $pid));
            }
            break;
            
        // User Plots
        case 'contact':
            
            // Capture contact cid
            $cid = $options['cid'];
            if (empty($cid)) {
                return;
            }
            
            // Add storage tab
            if (user_access('storage_view') || user_access('storage_edit') || user_access('storage_delete') || $cid == user_id()) {
                $plots = theme('table', 'storage_plot', array('cid' => $cid, 'show_export'=>false));
                page_add_content_bottom($page_data, $plots, 'Storage');
            }
            
            break;

         case 'plot_assign':
            
            // Capture contact cid
            $cid = $options['cid'];
            if (empty($cid)) {
                return;
            }
            page_set_title($page_data, 'Storage Plot Assignment');
            if (user_access('storage_view') || $cid == user_id()) {
                $plot_content = theme('user_plot_assign_form', array('cid' => $cid));
                page_add_content_top($page_data, $plot_content);
            }
            break;

         case 'plot_vacate':
            // Capture contact cid
            $pid = $options['pid'];
            if (empty($pid)) {
                return;
            }
            $cid = $options['cid'];
            if (empty($cid)) {
                return;
            }
            page_set_title($page_data, 'Vacate Storage Plot');
            if (user_access('storage_view') || $cid == user_id()) {
                $plot_content = theme('user_plot_vacate_form', array('pid' => $pid, 'cid'=>$cid));
                page_add_content_top($page_data, $plot_content);
            }
            break;

    }
}

// Request Handlers ////////////////////////////////////////////////////////////
// Put request handlers here

function command_storage_add() {
    // Verify permissions
    if (!user_access('storage_edit')) {
        error_register('Permission denied: storage_edit');
        return crm_url('storage');
    }
    storage_add($_POST);
    return crm_url('storage');
}

function command_storage_edit() {
    // Verify permissions
    if (!user_access('storage_edit')) {
        error_register('Permission denied: storage_edit');
        return crm_url('storage');
    }
    storage_edit($_POST);
    return crm_url('storage');
}

function command_storage_delete() {
    global $esc_post;
    // Verify permissions
    if (!user_access('storage_delete')) {
        error_register('Permission denied: storage_delete');
        return crm_url('storage');
    }
    storage_delete($_POST);
    return crm_url('storage');
}

function command_user_plot_assign() {
    // Verify permissions
    // if (!user_access('storage_edit') || $_POST['cid'] == user_id()) {
    //     error_register('Permission denied: storage_edit');
    //     return crm_url('contact&cid=' . $_POST['cid'] . '&tab=storage');
    // }
    storage_edit($_POST);
    return crm_url('contact&cid=' . $_POST['cid'] . '&tab=storage');
}

function command_user_plot_vacate() {
    // Verify permissions
    // if (!user_access('storage_edit') || $_POST['cid'] == user_id()) {
    //     error_register('Permission denied: storage_edit');
    //     return crm_url('contact&cid=' . $_POST['cid'] . '&tab=storage');
    // }
    user_plot_vacate($_POST);
    return crm_url('contact&cid=' . $_POST['cid'] . '&tab=storage');
}

function command_storage_reap() {
    global $esc_post;
    // Verify permissions
    if (!user_access('storage_edit')) {
        error_register('Permission denied: storage_edt');
        return crm_url('storage&tab=reap');
    }
    storage_reap($_POST);
    return crm_url('storage&tab=reap');
}

function command_storage_reap_config() {
    global $esc_post;
    // Verify permissions
    if (!user_access('storage_edit')) {
        error_register('Permission denied: storage_edt');
        return crm_url('storage&tab=config');
    }
    storage_reap_config($_POST);
    return crm_url('storage&tab=config');
}

function command_reap_filter () {
    // Set filter in session
    $_SESSION['reap_filter_option'] = $_GET['filter'];
    // Set filter
    if ($_GET['filter'] == 'weekOne') {
        $_SESSION['reap_filter'] = array('week'=>'one');
    }
    if ($_GET['filter'] == 'weekTwo') {
        $_SESSION['reap_filter'] = array('week'=>'two');
    }
    if ($_GET['filter'] == 'weekThree') {
        $_SESSION['reap_filter'] = array('week'=>'three');
    }
    if ($_GET['filter'] == 'weekFour') {
        $_SESSION['reap_filter'] = array('week'=>'four');
    }
    return crm_url('storage&tab=reap');
}
