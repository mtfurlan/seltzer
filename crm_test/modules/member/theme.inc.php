<?php

/*
    Copyright 2009-2017 Edward L. Platt <ed@elplatt.com>

    This file is part of the Seltzer CRM Project
    theme.inc.php - Member module - theming

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
/**
 * Return the themed html for a table of members.
 *
 * @param $opts Options to pass to member_data().
 * @return The themed html string.
*/
function theme_member_table ($opts = NULL) {
    return theme('table', crm_get_table('member', $opts));
}

/**
 * Return the themed html for a single member's contact info.
 *
 * @param $opts The options to pass to member_contact_data().
 * @return The themed html string.
*/
function theme_member_contact_table ($opts = NULL) {
    return theme('table_vertical', crm_get_table('member_contact', $opts));
}

/**
 * Returned the themed html for edit member form.
 *
 * @param $cid The cid of the member to edit.
 * @return The themed html.
*/
function theme_member_edit_form ($cid) {
    return theme('form', crm_get_form('member_edit', $cid));
}

/**
 * @return The themed html for a member filter form.
*/
function theme_member_filter_form () {
    return theme('form', crm_get_form('member_filter'));
}

/**
 * @return The themed html for a member voting report.
*/
function theme_member_voting_report () {
    return theme('table', crm_get_table('member_voting_report'));
}

/**
 * Return the themed html for a membership table.
 *
 * @param $opts The options to pass to member_membership_data().
 * @return The themed html string.
 */
function theme_member_membership_table ($opts = NULL) {
    return theme('table', crm_get_table('member_membership', $opts));
}

/**
 * Return the themed html for a membership add form.
 *
 * @param $cid the cid of the member who owns the membership
 * @return The themed html string.
 */
function theme_member_membership_add_form ($cid) {
    return theme('form', crm_get_form('member_membership_add', $cid));
}

/**
 * Return the themed html for a membership edit form.
 *
 * @param $sid The sid of the membership to edit.
 * @return The themed html string.
*/
function theme_member_membership_edit_form ($sid) {
    return theme('form', crm_get_form('member_membership_edit', $sid));
}

/**
 * Return the themed html for a membership plan table.
 *
 * @param $opts The options to pass to member_plan_data().
 * @return The themed html string.
 */
function theme_member_plan_table ($opts = NULL) {
    return theme('table', crm_get_table('member_plan', $opts));
}

/**
 * Return the themed html for a membership plan add form.
 *
 * @return The themed html string.
 */
function theme_member_plan_add_form () {
    return theme('form', crm_get_form('member_plan_add'));
}

/**
 * Return the themed html for a membership plan edit form.
 *
 * @param $pid The pid of the membership plan to edit.
 * @return The themed html string.
*/
function theme_member_plan_edit_form ($pid) {
    return theme('form', crm_get_form('member_plan_edit', $pid));
}

/**
 * Return the themed html for a member details table.
 *
 * @param $opts The options to pass to member_data().
 * @return The themed html string.
 */
function theme_member_details_table ($opts = NULL) {
    return theme('table_vertical', crm_get_table('member_details', $opts));
}

/**
 * Return the themed html for a contact's name.
 *
 * @param $cid The cid of the contact.
 * @return The themed html string.
 */
function theme_member_contact_name ($cid) {

    // Get member data
    $data = member_data(array('cid' => $cid));
    if (count($data) < 1) {
        return '';
    }

    $output = member_name(
        $data[0]['contact']['firstName']
        , $data[0]['contact']['middleName']
        , $data[0]['contact']['lastName']
    );
    return $output;
}

/**
 * Return the themed html description for a plan.
 *
 * @param $pid The pid of the plan.
 * @return The themed html string.
 */
function theme_member_plan_description ($pid) {

    // Get plan data
    $data = member_plan_data(array('pid' => $pid));
    if (count($data) < 1) {
        return '';
    }

    $output = $data[0]['name'] . ' : ' . $data[0]['price'];

    return $output;
}

/**
 * Return the text of an email notifying administrators that a user has been created.
 * @param $cid The contact id of the new member.
 */
function theme_member_created_email ($cid) {

    // Get info on the logged in user
    $data = member_contact_data(array('cid'=>user_id()));
    $admin = $data[0];
    $adminName = theme_contact_name($admin['cid']);

    // Get info on member
    $data = member_data(array('cid'=>$cid));
    $member = $data[0];
    $contact = $member['contact'];
    $name = theme_contact_name($contact['cid']);

    // Get info on member's plan
    $data = member_membership_data(array('cid'=>$cid, $filter=>array('active'=>true)));
    $date = $data[0]['start'];
    $plan = $data[0]['plan']['name'];

    $output = "<p>Contact info:<br/>\n";
    $output .= "Name: $name<br/>\n";
    $output .= "Email: $contact[email]<br/>\n";
    $output .= "Phone: $contact[phone]\n</p>\n";
    $output .= "<p>Membership info:<br/>\n";
    $output .= "Plan: $plan<br/>\n";
    $output .= "Start date: $date\n</p>\n";
    if (user_id()){
        $output .= "<p>Entered by: $adminName</p>\n";
    } else {
        $output .= "<p>User self-registered</p>\n";
    }
    return $output;
}

/**
 * Return the text of an email welcoming a new member.
 * @param $cid The contact id of the new member.
 * @param $confirm_url The url for the new user to confirm their email.
 */
function theme_member_welcome_email ($cid, $confirm_url) {
    $contact = crm_get_one('contact', array('cid'=>$cid));
    $vars = array(
        'type' => 'welcome'
        , 'confirm_url' => $confirm_url
        , 'username' => $contact['user']['username']
    );
    return template_render('email', $vars);
}

/**
 * Theme a plan name.
 *
 * @param $plan The plan data structure or pid.
 * @param $link True if the name should be a link (default: false).
 * @param $path The path that should be linked to.  The pid will always be added
 *   as a parameter.
 *
 * @return the name string.
 */
function theme_member_plan_name ($plan, $link = false, $path = 'plan') {
    if (!is_array($plan)) {
        $plan = crm_get_one('member_plan', array('pid'=>$plan));
    }
    $name = $plan['name'];
    if ($link) {
        $url_opts = array('query' => array('pid' => $plan['pid']));
        $name = crm_link($name, $path, $url_opts);
    }
    return $name;
}
