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

require_once __DIR__ . '/moduleFunctions.php';

if (!isActionAccessible($guid, $connection2, mailingModulePath('name_send.php'))) {
    $page->addError(__('You do not have access to this action.'));
} else {
    $mailingCampaignID = (int) ($_POST['mailingCampaignID'] ?? $_GET['mailingCampaignID'] ?? 0);
    if ($mailingCampaignID <= 0) {
        $page->addError(__('Invalid campaign selected.'));
        return;
    }

    try {
        $campaignQuery = $connection2->prepare(
            "SELECT mailingCampaignID, name, subject, senderEmail, status FROM mailingCampaign WHERE mailingCampaignID = :mailingCampaignID"
        );
        $campaignQuery->execute(['mailingCampaignID' => $mailingCampaignID]);
        $campaign = $campaignQuery->fetch(PDO::FETCH_ASSOC);

        $countQuery = $connection2->prepare(
            "SELECT
                SUM(CASE WHEN sendStatus = 'Pending' THEN 1 ELSE 0 END) AS pendingCount,
                SUM(CASE WHEN sendStatus = 'Sent' THEN 1 ELSE 0 END) AS sentCount,
                SUM(CASE WHEN sendStatus = 'Failed' THEN 1 ELSE 0 END) AS failedCount
             FROM mailingRecipient
             WHERE mailingCampaignID = :mailingCampaignID"
        );
        $countQuery->execute(['mailingCampaignID' => $mailingCampaignID]);
        $counts = $countQuery->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $page->addError(__('Unable to load campaign send details.'));
        return;
    }

    if (empty($campaign)) {
        $page->addError(__('Campaign not found.'));
        return;
    }

    $actionURL = $gibbon->session->get('absoluteURL') . '/modules/' . $gibbon->session->get('module') . '/name_sendProcess.php';
    $batchSize = (int) mailingGetSetting($connection2, 'sendBatchSize', 100);
    if ($batchSize <= 0) {
        $batchSize = 100;
    }

    echo '<h2>' . __('Send Campaign Now') . '</h2>';
    echo '<p><b>' . __('Campaign') . ':</b> ' . mailingH($campaign['name']) . '</p>';
    echo '<p><b>' . __('Subject') . ':</b> ' . mailingH($campaign['subject']) . '</p>';
    echo '<p><b>' . __('Sender') . ':</b> ' . mailingH($campaign['senderEmail']) . '</p>';
    echo '<p><b>' . __('Recipients') . ':</b> ' . sprintf(__('Pending: %1$s, Sent: %2$s, Failed: %3$s'), (int) ($counts['pendingCount'] ?? 0), (int) ($counts['sentCount'] ?? 0), (int) ($counts['failedCount'] ?? 0)) . '</p>';
    echo '<p>' . sprintf(__('This action processes up to %1$s pending recipients in one run. Run it again to continue sending remaining recipients.'), $batchSize) . '</p>';

    echo '<form method="post" action="' . mailingH($actionURL) . '">';
    echo '<input type="hidden" name="mailingCampaignID" value="' . $mailingCampaignID . '">';
    echo '<input type="submit" value="' . __('Process Send Batch') . '">';
    echo '</form>';
}
