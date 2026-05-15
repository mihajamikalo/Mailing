<?php
// USE ;end TO SEPARATE SQL STATEMENTS. DON'T USE ;end IN ANY OTHER PLACES!

$sql = [];
$count = 0;

// v1.0.00
$sql[$count][0] = "1.0.00";
$sql[$count][1] = "-- Initial release, no update required.";

// v1.1.00
$count++;
$sql[$count][0] = "1.1.00";
$sql[$count][1] = "
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
;end
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
;end
INSERT INTO gibbonSetting (scope, name, value, description, category)
SELECT 'Mailing', 'sendBatchSize', '100', 'Maximum recipients processed per Send Now action.', 'Module'
WHERE NOT EXISTS (
    SELECT 1 FROM gibbonSetting WHERE scope = 'Mailing' AND name = 'sendBatchSize'
);
";
