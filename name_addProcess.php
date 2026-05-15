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

$URL = $gibbon->session->get('absoluteURL') . '/index.php?q=' . mailingModulePath('name_add.php');

$canAccessAdd = isActionAccessible($guid, $connection2, mailingModulePath('name_add.php'))
    || isActionAccessible($guid, $connection2, mailingModulePath('name_view.php'));

if (!$canAccessAdd) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
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

    if ($name === '' || $audienceSummary === '' || $subject === '' || $senderName === '' || $senderEmail === '' || $contentHTML === '' || empty($parsedRecipients)) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    }

    if (!in_array($status, ['Draft', 'Scheduled'], true)) {
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
    $creator = $gibbon->session->get('gibbonPersonID') ?: null;

    try {
        $connection2->beginTransaction();

        $sql = "INSERT INTO mailingCampaign
                (name, audienceSummary, subject, senderName, senderEmail, replyToEmail, contentHTML, status, scheduledAt, totalRecipients, gibbonPersonIDCreator)
                VALUES (:name, :audienceSummary, :subject, :senderName, :senderEmail, :replyToEmail, :contentHTML, :status, :scheduledAt, :totalRecipients, :creator)";
        $data = [
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
            'creator' => $creator,
        ];

        $result = $connection2->prepare($sql);
        $result->execute($data);

        $mailingCampaignID = (int) $connection2->lastInsertId();
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

    $URL = $gibbon->session->get('absoluteURL') . '/index.php?q=' . mailingModulePath('name_view.php') . '&return=success0';
    header("Location: {$URL}");
}
