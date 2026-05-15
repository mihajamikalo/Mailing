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

if (!isActionAccessible($guid, $connection2, mailingModulePath('name_send.php'))) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

$mailingCampaignID = (int) ($_POST['mailingCampaignID'] ?? 0);
if ($mailingCampaignID <= 0) {
    $URL .= '&return=error3';
    header("Location: {$URL}");
    exit;
}

$batchSize = (int) mailingGetSetting($connection2, 'sendBatchSize', 100);
if ($batchSize <= 0) {
    $batchSize = 100;
}
$attemptedCount = 0;
$sentCount = 0;
$failedCount = 0;
$runStatus = 'Started';
$notes = null;

try {
    $campaignQuery = $connection2->prepare(
        "SELECT mailingCampaignID, subject, senderName, senderEmail, replyToEmail, contentHTML
         FROM mailingCampaign
         WHERE mailingCampaignID = :mailingCampaignID"
    );
    $campaignQuery->execute(['mailingCampaignID' => $mailingCampaignID]);
    $campaign = $campaignQuery->fetch(PDO::FETCH_ASSOC);

    if (empty($campaign)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $recipientQuery = $connection2->prepare(
        "SELECT mailingRecipientID, recipientEmail
         FROM mailingRecipient
         WHERE mailingCampaignID = :mailingCampaignID
           AND sendStatus IN ('Pending', 'Failed')
         ORDER BY mailingRecipientID ASC
         LIMIT {$batchSize}"
    );
    $recipientQuery->execute(['mailingCampaignID' => $mailingCampaignID]);
    $recipients = $recipientQuery->fetchAll(PDO::FETCH_ASSOC);

    if (empty($recipients)) {
        $runStatus = 'NoPendingRecipients';
        $notes = 'No pending recipients in queue.';
    } else {
        $updateSent = $connection2->prepare(
            "UPDATE mailingRecipient
             SET sendStatus = 'Sent',
                 sentAt = NOW(),
                 lastAttemptAt = NOW(),
                 attemptCount = attemptCount + 1,
                 lastError = NULL
             WHERE mailingRecipientID = :mailingRecipientID"
        );

        $updateFailed = $connection2->prepare(
            "UPDATE mailingRecipient
             SET sendStatus = 'Failed',
                 lastAttemptAt = NOW(),
                 attemptCount = attemptCount + 1,
                 lastError = :lastError
             WHERE mailingRecipientID = :mailingRecipientID"
        );

        foreach ($recipients as $recipient) {
            $attemptedCount++;
            $ok = mailingSendHtmlEmail(
                $recipient['recipientEmail'],
                $campaign['subject'],
                $campaign['contentHTML'],
                $campaign['senderName'],
                $campaign['senderEmail'],
                $campaign['replyToEmail']
            );

            if ($ok) {
                $sentCount++;
                $updateSent->execute(['mailingRecipientID' => (int) $recipient['mailingRecipientID']]);
            } else {
                $failedCount++;
                $updateFailed->execute([
                    'mailingRecipientID' => (int) $recipient['mailingRecipientID'],
                    'lastError' => 'mail() returned false',
                ]);
            }
        }

        $runStatus = $failedCount > 0 ? 'CompletedWithErrors' : 'Completed';
    }

    $logInsert = $connection2->prepare(
        "INSERT INTO mailingSendLog
         (mailingCampaignID, startedAt, finishedAt, attemptedCount, sentCount, failedCount, runStatus, notes, gibbonPersonIDRunner)
         VALUES (:mailingCampaignID, NOW(), NOW(), :attemptedCount, :sentCount, :failedCount, :runStatus, :notes, :runner)"
    );
    $logInsert->execute([
        'mailingCampaignID' => $mailingCampaignID,
        'attemptedCount' => $attemptedCount,
        'sentCount' => $sentCount,
        'failedCount' => $failedCount,
        'runStatus' => $runStatus,
        'notes' => $notes,
        'runner' => $gibbon->session->get('gibbonPersonID') ?: null,
    ]);

    $remainingQuery = $connection2->prepare(
        "SELECT COUNT(*) FROM mailingRecipient WHERE mailingCampaignID = :mailingCampaignID AND sendStatus IN ('Pending', 'Failed')"
    );
    $remainingQuery->execute(['mailingCampaignID' => $mailingCampaignID]);
    $remaining = (int) $remainingQuery->fetchColumn();

    if ($remaining === 0) {
        $statusQuery = $connection2->prepare(
            "UPDATE mailingCampaign SET status = 'Sent', scheduledAt = NULL WHERE mailingCampaignID = :mailingCampaignID"
        );
        $statusQuery->execute(['mailingCampaignID' => $mailingCampaignID]);
    } else {
        $statusQuery = $connection2->prepare(
            "UPDATE mailingCampaign SET status = 'Scheduled' WHERE mailingCampaignID = :mailingCampaignID"
        );
        $statusQuery->execute(['mailingCampaignID' => $mailingCampaignID]);
    }
} catch (PDOException $e) {
    $URL .= '&return=error2';
    header("Location: {$URL}");
    exit;
}

$URL .= '&return=success0';
header("Location: {$URL}");
exit;
