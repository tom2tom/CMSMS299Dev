<?php
/*
Job: update news items' status in accord with their start/end times
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is part of CMS Made Simple module: News

Refer to license and other details at the top of file News.module.php
*/
namespace News;

use CMSMS\AppSingle;
use CMSMS\Async\CronJob;
use CMSMS\Async\RecurType;
use CMS_DB_PREFIX;

class AdjustStatusJob extends CronJob
{
    public function __construct($params = [])
    {
        parent:: __construct($params);
        $this->name = 'News\\AdjustStatus';
        $this->frequency = RecurType::RECUR_HOURLY;
    }

    /**
     * @ignore
     * @return int 0|1|2 indicating execution status
     */
    public function execute()
    {
        $time = time();
        $db = AppSingle::Db();
        $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET status=\'archived\' WHERE (status=\'published\' OR status=\'final\') AND end_time IS NOT NULL AND end_time BETWEEN 1 AND ?';
        $db->Execute($query,[$time]);
        $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET status=\'published\' WHERE status=\'final\' AND start_time IS NOT NULL AND start_time BETWEEN 1 AND ?';
        $db->Execute($query,[$time]);
        return 2; // TODO
    }
}
