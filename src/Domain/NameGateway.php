<?php
namespace Gibbon\Module\Mailing\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryableGateway;

/**
 * Name Gateway
 *
 * @version v21
 * @since   v21
 */
class NameGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'mailingCampaign';
    private static $primaryKey = 'mailingCampaignID';
    private static $searchableColumns = ['name', 'audienceSummary', 'subject', 'senderEmail'];
}
