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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

include '../../gibbon.php';
include './moduleFunctions.php';

$URL = $gibbon->session->get('absoluteURL') . '/index.php?q=' . mailingModulePath('name_view.php');

if (!isActionAccessible($guid, $connection2, mailingModulePath('name_edit.php'))) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $mailingCampaignID = (int) ($_POST['mailingCampaignID'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $audienceSummary = trim($_POST['audienceSummary'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $senderName = trim($_POST['senderName'] ?? '');
    $senderEmail = trim($_POST['senderEmail'] ?? '');
    $replyToEmail = trim($_POST['replyToEmail'] ?? '');
    $status = trim($_POST['status'] ?? 'Draft');
    $scheduledAt = trim($_POST['scheduledAt'] ?? '');
    $recipientEmails = trim($_POST['recipientEmails'] ?? '');
    $contentHTML = trim($_POST['contentHTML'] ?? '');
    $parsedRecipients = mailingParseRecipients($recipientEmails);
    $totalRecipients = count($parsedRecipients);

    if ($mailingCampaignID <= 0 || $name === '' || $audienceSummary === '' || $subject === '' || $senderName === '' || $senderEmail === '' || $contentHTML === '' || empty($parsedRecipients)) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    }

    if (!in_array($status, ['Draft', 'Scheduled', 'Sent'], true)) {
        $status = 'Draft';
    }

    if ($status !== 'Scheduled') {
        $scheduledAt = null;
    } elseif (empty($scheduledAt)) {
        $scheduledAt = null;
    } else {
        $scheduledAt = str_replace('T', ' ', $scheduledAt) . ':00';
    }

    $replyToEmail = $replyToEmail !== '' ? $replyToEmail : null;

    try {
        $connection2->beginTransaction();

        $sql = "UPDATE mailingCampaign
                SET name = :name,
                    audienceSummary = :audienceSummary,
                    subject = :subject,
                    senderName = :senderName,
                    senderEmail = :senderEmail,
                    replyToEmail = :replyToEmail,
                    contentHTML = :contentHTML,
                    status = :status,
                    scheduledAt = :scheduledAt,
                    totalRecipients = :totalRecipients
                WHERE mailingCampaignID = :mailingCampaignID";

        $data = [
            'mailingCampaignID' => $mailingCampaignID,
            'name' => $name,
            'audienceSummary' => $audienceSummary,
            'subject' => $subject,
            'senderName' => $senderName,
            'senderEmail' => $senderEmail,
            'replyToEmail' => $replyToEmail,
            'contentHTML' => $contentHTML,
            'status' => $status,
            'scheduledAt' => $scheduledAt,
            'totalRecipients' => max(0, $totalRecipients),
        ];

        $result = $connection2->prepare($sql);
        $result->execute($data);
        mailingReplaceRecipients($connection2, $mailingCampaignID, $parsedRecipients);
        $connection2->commit();
    } catch (PDOException $e) {
        if ($connection2->inTransaction()) {
            $connection2->rollBack();
        }
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $URL .= '&return=success1';
    header("Location: {$URL}");
}
