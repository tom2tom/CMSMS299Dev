<?php

namespace CMSMS\Async;

interface JobManager
{
	public function trigger_async_processing();

	public function suspend_async_processing($state=TRUE);

	public function refresh_jobs($files=TRUE, $modules=TRUE);

	public function suspend_job($name, $module, $state=TRUE);

	public function save_job($job);

	public function delete_job($job);
}
