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

$modulePath = mailingModulePath('name_view.php');

if (!isActionAccessible($guid, $connection2, $modulePath)) {
    $page->addError(__('You do not have access to this action.'));
} else {
    $newURL = $gibbon->session->get('absoluteURL') . '/index.php?q=' . mailingModulePath('name_add.php');

    echo '<h2>' . __('Mailing Campaigns') . '</h2>';
    echo '<p>' . __('Create and manage campaigns for school-wide communications.') . '</p>';
    echo '<form style="margin: 8px 0 12px 0;" method="get" action="' . mailingH($gibbon->session->get('absoluteURL') . '/index.php') . '">';
    echo '<input type="hidden" name="q" value="' . mailingH(mailingModulePath('name_add.php')) . '">';
    echo '<input type="submit" value="' . __('Create Campaign') . '" style="font-weight:bold;">';
    echo '</form>';

    try {
        $sql = "SELECT c.mailingCampaignID, c.name, c.audienceSummary, c.subject, c.senderEmail, c.status, c.totalRecipients, c.scheduledAt, c.updatedAt,
                       SUM(CASE WHEN r.sendStatus = 'Pending' THEN 1 ELSE 0 END) AS pendingCount,
                       SUM(CASE WHEN r.sendStatus = 'Sent' THEN 1 ELSE 0 END) AS sentCount,
                       SUM(CASE WHEN r.sendStatus = 'Failed' THEN 1 ELSE 0 END) AS failedCount
                FROM mailingCampaign c
                LEFT JOIN mailingRecipient r ON r.mailingCampaignID = c.mailingCampaignID
                GROUP BY c.mailingCampaignID
                ORDER BY updatedAt DESC";
        $result = $connection2->prepare($sql);
        $result->execute();
        $campaigns = $result->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $page->addError(__('Unable to load campaigns.'));
        return;
    }

    if (empty($campaigns)) {
        echo '<div class="error">' . __('No campaigns found. Start by creating one.') . '</div>';
        return;
    }

    echo '<table cellspacing="0" style="width: 100%;">';
    echo '<tr class="head">';
    echo '<th>' . __('Campaign') . '</th>';
    echo '<th>' . __('Audience') . '</th>';
    echo '<th>' . __('Subject') . '</th>';
    echo '<th>' . __('Sender') . '</th>';
    echo '<th>' . __('Status') . '</th>';
    echo '<th>' . __('Recipients') . '</th>';
    echo '<th>' . __('Delivery') . '</th>';
    echo '<th>' . __('Scheduled') . '</th>';
    echo '<th>' . __('Actions') . '</th>';
    echo '</tr>';

    foreach ($campaigns as $row) {
        $editURL = $gibbon->session->get('absoluteURL') . '/index.php?q=' . mailingModulePath('name_edit.php');
        $deleteURL = $gibbon->session->get('absoluteURL') . '/index.php?q=' . mailingModulePath('name_delete.php');
        $sendURL = $gibbon->session->get('absoluteURL') . '/index.php?q=' . mailingModulePath('name_send.php');

        echo '<tr>';
        echo '<td>' . mailingH($row['name']) . '</td>';
        echo '<td>' . mailingH($row['audienceSummary']) . '</td>';
        echo '<td>' . mailingH($row['subject']) . '</td>';
        echo '<td>' . mailingH($row['senderEmail']) . '</td>';
        echo '<td>' . mailingH($row['status']) . '</td>';
        echo '<td>' . (int) $row['totalRecipients'] . '</td>';
        echo '<td>' . sprintf(__('Sent: %1$s, Pending: %2$s, Failed: %3$s'), (int) $row['sentCount'], (int) $row['pendingCount'], (int) $row['failedCount']) . '</td>';
        echo '<td>' . mailingH(mailingFormatDateTime($row['scheduledAt'])) . '</td>';
        echo '<td>';
        echo '<form style="display:inline-block; margin:0 4px 0 0;" method="post" action="' . mailingH($editURL) . '">';
        echo '<input type="hidden" name="mailingCampaignID" value="' . (int) $row['mailingCampaignID'] . '">';
        echo '<input type="submit" value="' . __('Edit') . '">';
        echo '</form>';
        echo '<form style="display:inline-block; margin:0;" method="post" action="' . mailingH($deleteURL) . '">';
        echo '<input type="hidden" name="mailingCampaignID" value="' . (int) $row['mailingCampaignID'] . '">';
        echo '<input type="submit" value="' . __('Delete') . '">';
        echo '</form>';
        if ((int) $row['pendingCount'] > 0) {
            echo '<form style="display:inline-block; margin:0 0 0 4px;" method="post" action="' . mailingH($sendURL) . '">';
            echo '<input type="hidden" name="mailingCampaignID" value="' . (int) $row['mailingCampaignID'] . '">';
            echo '<input type="submit" value="' . __('Send Now') . '">';
            echo '</form>';
        }
        echo '</td>';
        echo '</tr>';
    }

    echo '</table>';
}
