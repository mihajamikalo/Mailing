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
along with this program.  If not, see <http:// www.gnu.org/licenses/>.
*/

// This file describes the module, including database tables

// Basic variables
$name        = 'Mailing';
$description = 'Mass mailing campaigns with list and content management.';
$entryURL    = 'name_view.php';
$type        = 'Additional';
$category    = 'School Admin';
$version     = '1.1.00';
$author      = 'Local Development';
$url         = '';

// Module tables & gibbonSettings entries
$moduleTables[] = "
CREATE TABLE mailingCampaign (
    mailingCampaignID INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    audienceSummary VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    senderName VARCHAR(120) NOT NULL,
    senderEmail VARCHAR(255) NOT NULL,
    replyToEmail VARCHAR(255) NULL,
    contentHTML MEDIUMTEXT NOT NULL,
    status ENUM('Draft', 'Scheduled', 'Sent') NOT NULL DEFAULT 'Draft',
    scheduledAt DATETIME NULL,
    totalRecipients INT UNSIGNED NOT NULL DEFAULT 0,
    gibbonPersonIDCreator INT UNSIGNED NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (mailingCampaignID),
    INDEX idx_status (status),
    INDEX idx_scheduledAt (scheduledAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$moduleTables[] = "
CREATE TABLE mailingRecipient (
    mailingRecipientID INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mailingCampaignID INT UNSIGNED NOT NULL,
    recipientName VARCHAR(120) NULL,
    recipientEmail VARCHAR(255) NOT NULL,
    sendStatus ENUM('Pending', 'Sent', 'Failed', 'Unsubscribed') NOT NULL DEFAULT 'Pending',
    attemptCount SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    lastAttemptAt DATETIME NULL,
    sentAt DATETIME NULL,
    lastError VARCHAR(500) NULL,
    createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (mailingRecipientID),
    UNIQUE KEY uniq_campaign_email (mailingCampaignID, recipientEmail),
    INDEX idx_campaign_status (mailingCampaignID, sendStatus),
    CONSTRAINT fk_mailingRecipient_campaign FOREIGN KEY (mailingCampaignID)
        REFERENCES mailingCampaign (mailingCampaignID)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$moduleTables[] = "
CREATE TABLE mailingSendLog (
    mailingSendLogID INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mailingCampaignID INT UNSIGNED NOT NULL,
    startedAt DATETIME NOT NULL,
    finishedAt DATETIME NULL,
    attemptedCount INT UNSIGNED NOT NULL DEFAULT 0,
    sentCount INT UNSIGNED NOT NULL DEFAULT 0,
    failedCount INT UNSIGNED NOT NULL DEFAULT 0,
    runStatus ENUM('Started', 'Completed', 'CompletedWithErrors', 'NoPendingRecipients') NOT NULL DEFAULT 'Started',
    notes VARCHAR(500) NULL,
    gibbonPersonIDRunner INT UNSIGNED NULL,
    PRIMARY KEY (mailingSendLogID),
    INDEX idx_sendlog_campaign_started (mailingCampaignID, startedAt),
    CONSTRAINT fk_mailingSendLog_campaign FOREIGN KEY (mailingCampaignID)
        REFERENCES mailingCampaign (mailingCampaignID)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Add gibbonSettings entries
$gibbonSetting[] = "('Mailing', 'defaultSenderName', '', 'Mass mailing default sender name', 'Module')";
$gibbonSetting[] = "('Mailing', 'defaultSenderEmail', '', 'Mass mailing default sender email', 'Module')";
$gibbonSetting[] = "('Mailing', 'sendBatchSize', '100', 'Maximum recipients processed per Send Now action.', 'Module')";

// Action rows 
// One array per action
$actionRows[] = [
    'name'                      => 'Campaigns',
    'precedence'                => '0',
    'category'                  => 'Campaign Management',
    'description'               => 'Create and manage mass mailing campaigns.',
    'URLList'                   => 'name_view.php,name_add.php,name_addProcess.php,name_edit.php,name_editProcess.php,name_delete.php,name_deleteProcess.php,name_send.php,name_sendProcess.php',
    'entryURL'                  => 'name_view.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'Y',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
];

// Hooks
$hooks[] = ''; // Serialised array to create hook and set options. See Hooks documentation online.
