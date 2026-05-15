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

if (!isActionAccessible($guid, $connection2, mailingModulePath('name_delete.php'))) {
    $page->addError(__('You do not have access to this action.'));
}
else {
    $mailingCampaignID = (int) ($_POST['mailingCampaignID'] ?? $_GET['mailingCampaignID'] ?? 0);
    if ($mailingCampaignID <= 0) {
        $page->addError(__('Invalid campaign selected.'));
        return;
    }

    $actionURL = $gibbon->session->get('absoluteURL') . '/modules/' . $gibbon->session->get('module') . '/name_deleteProcess.php';

    echo '<h2>' . __('Delete Mailing Campaign') . '</h2>';
    echo '<p>' . __('This action cannot be undone.') . '</p>';
    echo '<form method="post" action="' . mailingH($actionURL) . '">';
    echo '<input type="hidden" name="mailingCampaignID" value="' . $mailingCampaignID . '">';
    echo '<input type="submit" value="' . __('Confirm Delete') . '">';
    echo '</form>';
}
