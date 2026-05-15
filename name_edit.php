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

if (!isActionAccessible($guid, $connection2, mailingModulePath('name_edit.php'))) {
    $page->addError(__('You do not have access to this action.'));
} else {
    $mailingCampaignID = (int) ($_POST['mailingCampaignID'] ?? $_GET['mailingCampaignID'] ?? 0);
    if ($mailingCampaignID <= 0) {
        $page->addError(__('Invalid campaign selected.'));
        return;
    }

    try {
        $sql = "SELECT * FROM mailingCampaign WHERE mailingCampaignID = :mailingCampaignID";
        $result = $connection2->prepare($sql);
        $result->execute(['mailingCampaignID' => $mailingCampaignID]);
        $campaign = $result->fetch(PDO::FETCH_ASSOC);

        $recipientQuery = $connection2->prepare(
            "SELECT recipientEmail FROM mailingRecipient WHERE mailingCampaignID = :mailingCampaignID ORDER BY recipientEmail ASC"
        );
        $recipientQuery->execute(['mailingCampaignID' => $mailingCampaignID]);
        $recipientRows = $recipientQuery->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $campaign = false;
        $recipientRows = [];
    }

    if (empty($campaign)) {
        $page->addError(__('Campaign not found.'));
        return;
    }

    $actionURL = $gibbon->session->get('absoluteURL') . '/modules/' . $gibbon->session->get('module') . '/name_editProcess.php';
    $scheduled = '';
    if (!empty($campaign['scheduledAt'])) {
        $scheduled = date('Y-m-d\TH:i', strtotime($campaign['scheduledAt']));
    }
    $recipientText = mailingRecipientsToText($recipientRows);

    echo '<h2>' . __('Edit Mailing Campaign') . '</h2>';
    echo '<form method="post" action="' . mailingH($actionURL) . '">';
    echo '<input type="hidden" name="mailingCampaignID" value="' . (int) $campaign['mailingCampaignID'] . '">';
    echo '<table cellspacing="0" style="width: 100%;">';

    echo '<tr><td><b>' . __('Campaign Name') . ' *</b></td><td><input type="text" name="name" maxlength="120" required style="width:100%;" value="' . mailingH($campaign['name']) . '"></td></tr>';
    echo '<tr><td><b>' . __('Audience Summary') . ' *</b></td><td><input type="text" name="audienceSummary" maxlength="255" required style="width:100%;" value="' . mailingH($campaign['audienceSummary']) . '"></td></tr>';
    echo '<tr><td><b>' . __('Estimated Recipients') . '</b></td><td><input type="number" name="totalRecipients" min="0" value="' . (int) $campaign['totalRecipients'] . '" style="width:200px;"></td></tr>';
    echo '<tr><td><b>' . __('Email Subject') . ' *</b></td><td><input type="text" name="subject" maxlength="255" required style="width:100%;" value="' . mailingH($campaign['subject']) . '"></td></tr>';
    echo '<tr><td><b>' . __('Sender Name') . ' *</b></td><td><input type="text" name="senderName" maxlength="120" required style="width:100%;" value="' . mailingH($campaign['senderName']) . '"></td></tr>';
    echo '<tr><td><b>' . __('Sender Email') . ' *</b></td><td><input type="email" name="senderEmail" maxlength="255" required style="width:100%;" value="' . mailingH($campaign['senderEmail']) . '"></td></tr>';
    echo '<tr><td><b>' . __('Reply-To Email') . '</b></td><td><input type="email" name="replyToEmail" maxlength="255" style="width:100%;" value="' . mailingH($campaign['replyToEmail']) . '"></td></tr>';
    echo '<tr><td><b>' . __('Status') . '</b></td><td>';
    echo '<select name="status">';
    echo '<option value="Draft"' . ($campaign['status'] === 'Draft' ? ' selected' : '') . '>' . __('Draft') . '</option>';
    echo '<option value="Scheduled"' . ($campaign['status'] === 'Scheduled' ? ' selected' : '') . '>' . __('Scheduled') . '</option>';
    echo '<option value="Sent"' . ($campaign['status'] === 'Sent' ? ' selected' : '') . '>' . __('Sent') . '</option>';
    echo '</select>';
    echo '</td></tr>';
    echo '<tr><td><b>' . __('Scheduled At') . '</b></td><td><input type="datetime-local" name="scheduledAt" value="' . mailingH($scheduled) . '"></td></tr>';
    echo '<tr><td><b>' . __('Recipients') . ' *</b></td><td><textarea name="recipientEmails" rows="8" required style="width:100%;">' . mailingH($recipientText) . '</textarea></td></tr>';
    echo '<tr><td><b>' . __('Message (HTML)') . ' *</b></td><td><textarea name="contentHTML" rows="12" required style="width:100%;">' . mailingH($campaign['contentHTML']) . '</textarea></td></tr>';
    echo '<tr><td></td><td><input type="submit" value="' . __('Update Campaign') . '"></td></tr>';
    echo '</table>';
    echo '</form>';
}
