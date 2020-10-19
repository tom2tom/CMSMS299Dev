<?php
namespace CMSMS\Async;

interface JobManager
{
	public function trigger_async_processing();

	public function suspend_async_processing($state = true);

	public function refresh_jobs($files = true, $modules = true);

	public function suspend_job($name, $module, $state = true);

	public function save_job($job);

	public function delete_job($job);
}
