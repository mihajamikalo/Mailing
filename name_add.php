<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

$canAccessAdd = isActionAccessible($guid, $connection2, mailingModulePath('name_add.php'))
    || isActionAccessible($guid, $connection2, mailingModulePath('name_view.php'));

if (!$canAccessAdd) {
    $page->addError(__('You do not have access to this action.'));
} else {
    $actionURL = $gibbon->session->get('absoluteURL') . '/modules/' . $gibbon->session->get('module') . '/name_addProcess.php';

    echo '<h2>' . __('Create Mailing Campaign') . '</h2>';
    echo '<form method="post" action="' . mailingH($actionURL) . '">';
    echo '<table cellspacing="0" style="width: 100%;">';

    echo '<tr><td><b>' . __('Campaign Name') . ' *</b></td><td><input type="text" name="name" maxlength="120" required style="width:100%;"></td></tr>';
    echo '<tr><td><b>' . __('Audience Summary') . ' *</b></td><td><input type="text" name="audienceSummary" maxlength="255" required style="width:100%;" placeholder="' . mailingH(__('e.g. All Parents - Grade 7')) . '"></td></tr>';
    echo '<tr><td><b>' . __('Estimated Recipients') . '</b></td><td><input type="number" name="totalRecipients" min="0" value="0" style="width:200px;"></td></tr>';
    echo '<tr><td><b>' . __('Email Subject') . ' *</b></td><td><input type="text" name="subject" maxlength="255" required style="width:100%;"></td></tr>';
    echo '<tr><td><b>' . __('Sender Name') . ' *</b></td><td><input type="text" name="senderName" maxlength="120" required style="width:100%;"></td></tr>';
    echo '<tr><td><b>' . __('Sender Email') . ' *</b></td><td><input type="email" name="senderEmail" maxlength="255" required style="width:100%;"></td></tr>';
    echo '<tr><td><b>' . __('Reply-To Email') . '</b></td><td><input type="email" name="replyToEmail" maxlength="255" style="width:100%;"></td></tr>';
    echo '<tr><td><b>' . __('Status') . '</b></td><td>';
    echo '<select name="status">';
    echo '<option value="Draft">' . __('Draft') . '</option>';
    echo '<option value="Scheduled">' . __('Scheduled') . '</option>';
    echo '</select>';
    echo '</td></tr>';
    echo '<tr><td><b>' . __('Scheduled At') . '</b></td><td><input type="datetime-local" name="scheduledAt"></td></tr>';
    echo '<tr><td><b>' . __('Recipients') . ' *</b></td><td><textarea name="recipientEmails" rows="8" required style="width:100%;" placeholder="' . mailingH(__('One email per line, or separated by commas.')) . '"></textarea></td></tr>';
    echo '<tr><td><b>' . __('Message (HTML)') . ' *</b></td><td><textarea name="contentHTML" rows="12" required style="width:100%;"></textarea></td></tr>';

    echo '<tr><td></td><td><input type="submit" value="' . __('Save Campaign') . '"></td></tr>';
    echo '</table>';
    echo '</form>';
}
