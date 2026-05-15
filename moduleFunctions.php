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

/**
 * Build a module URL path from a file name.
 */
function mailingModulePath($file)
{
    global $gibbon;

    $module = $gibbon->session->get('module') ?: 'Mailing';

    return '/modules/' . $module . '/' . ltrim($file, '/');
}

/**
 * Convert a date-time value to a human-readable local format.
 */
function mailingFormatDateTime($value)
{
    if (empty($value)) {
        return '';
    }

    $time = strtotime($value);
    if ($time === false) {
        return '';
    }

    return date('Y-m-d H:i', $time);
}

/**
 * Escape output for HTML.
 */
function mailingH($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Parse a recipient text block into unique valid email rows.
 */
function mailingParseRecipients($recipientInput)
{
    $raw = preg_split('/[\r\n,;]+/', (string) $recipientInput);
    $items = [];
    $seen = [];

    foreach ($raw as $entry) {
        $email = strtolower(trim($entry));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        if (isset($seen[$email])) {
            continue;
        }

        $seen[$email] = true;
        $items[] = ['recipientName' => null, 'recipientEmail' => $email];
    }

    return $items;
}

/**
 * Replace all recipients for a campaign.
 */
function mailingReplaceRecipients(PDO $connection2, $mailingCampaignID, array $recipients)
{
    $delete = $connection2->prepare('DELETE FROM mailingRecipient WHERE mailingCampaignID = :mailingCampaignID');
    $delete->execute(['mailingCampaignID' => (int) $mailingCampaignID]);

    if (empty($recipients)) {
        return;
    }

    $insert = $connection2->prepare(
        'INSERT INTO mailingRecipient (mailingCampaignID, recipientName, recipientEmail, sendStatus)
         VALUES (:mailingCampaignID, :recipientName, :recipientEmail, :sendStatus)'
    );

    foreach ($recipients as $recipient) {
        $insert->execute([
            'mailingCampaignID' => (int) $mailingCampaignID,
            'recipientName' => $recipient['recipientName'],
            'recipientEmail' => $recipient['recipientEmail'],
            'sendStatus' => 'Pending',
        ]);
    }
}

/**
 * Convert campaign recipients to textarea text.
 */
function mailingRecipientsToText(array $recipients)
{
    $emails = [];
    foreach ($recipients as $recipient) {
        if (!empty($recipient['recipientEmail'])) {
            $emails[] = $recipient['recipientEmail'];
        }
    }

    return implode("\n", $emails);
}

/**
 * Send a single HTML email using PHP mail().
 */
function mailingSendHtmlEmail($to, $subject, $html, $senderName, $senderEmail, $replyToEmail = null)
{
    $safeSenderName = str_replace(['"', "\r", "\n"], '', (string) $senderName);
    $safeSenderEmail = str_replace(["\r", "\n"], '', (string) $senderEmail);
    $safeReplyTo = str_replace(["\r", "\n"], '', (string) $replyToEmail);
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: "' . $safeSenderName . '" <' . $safeSenderEmail . '>';

    if (!empty($safeReplyTo)) {
        $headers[] = 'Reply-To: <' . $safeReplyTo . '>';
    }

    return @mail((string) $to, (string) $subject, (string) $html, implode("\r\n", $headers));
}

/**
 * Fetch a module setting value with fallback.
 */
function mailingGetSetting(PDO $connection2, $name, $default = null)
{
    try {
        $query = $connection2->prepare(
            "SELECT value
             FROM gibbonSetting
             WHERE scope = 'Mailing' AND name = :name
             LIMIT 1"
        );
        $query->execute(['name' => $name]);
        $value = $query->fetchColumn();
    } catch (PDOException $e) {
        return $default;
    }

    return $value !== false && $value !== null && $value !== '' ? $value : $default;
}
