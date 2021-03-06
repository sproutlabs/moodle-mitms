<?php

require_once('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/hierarchy/type/organisation/lib.php');
require_once($CFG->dirroot.'/local/js/lib/setup.php');


///
/// Setup / loading data
///

// Competency id
$userid = required_param('user', PARAM_INT);

// Parent id
$parentid = optional_param('parentid', 0, PARAM_INT);

// Framework id
$frameworkid = optional_param('frameworkid', 0, PARAM_INT);

// Only return generated tree html
$treeonly = optional_param('treeonly', false, PARAM_BOOL);

// Setup page
require_login();

// Check permissions
$personalcontext = get_context_instance(CONTEXT_USER, $userid);
$can_edit = false;
if (has_capability('moodle/local:assignuserposition', get_context_instance(CONTEXT_SYSTEM))) {
    $can_edit = true;
}
elseif (has_capability('moodle/local:assignuserposition', $personalcontext)) {
    $can_edit = true;
}
elseif ($USER->id == $user->id &&
    has_capability('moodle/local:assignselfposition', get_context_instance(CONTEXT_SYSTEM))) {
    $can_edit = true;
}

if (!$can_edit) {
    error('You do not have the permissions to assign this user a position');
}

// Setup hierarchy object
$hierarchy = new organisation();

// Load framework
$framework = $hierarchy->get_framework($frameworkid, true);
if ( $framework ){
    // Load items to display
    $positions = $hierarchy->get_items_by_parent($parentid);
} else {
    $positions = array();
}

// Determine which error message
if ( $parentid ){
    $errorstr = 'nochildorganisations';
} else {
    if ( $frameworkid ){
        $errorstr = 'noorganisationsinframework';
    } else {
        $errorstr = 'noorganisation';
    }
}

///
/// Display page
///

// If parent id is not supplied, we must be displaying the main page
if (!$parentid) {

    if ($treeonly) {
        echo build_treeview(
            $positions,
            get_string($errorstr, $hierarchy->prefix),
            $hierarchy
        );
        exit;
    }

?>

<div class="selectorganisation">


<h2>
<?php 
    echo get_string('chooseorganisation', $hierarchy->prefix);
    echo dialog_display_currently_selected(get_string('currentlyselected', $hierarchy->prefix));
?>
</h2>

<?php $hierarchy->display_framework_selector('', true) ?>

<ul class="treeview filetree picker">
<?php
}

echo build_treeview(
    $positions,
    get_string($errorstr, $hierarchy->prefix),
    $hierarchy
);

// If no parent id, close list
if (!$parentid) {
    echo '</ul></div>';
}
