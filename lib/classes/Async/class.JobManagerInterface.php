<?php

namespace CMSMS\Async;

interface JobManagerInterface
{
	public function load_jobs_by_module($module_name);

	public function load_job_by_id($job_id);

	public function load_job(Job &$job);

	public function unload_jobs_by_module($module_name);

	public function unload_job_by_id($job_id);

	public function unload_job_by_name($module_name, $job_name);

	public function unload_job(Job &$job);

	public function trigger_async_processing($deep=FALSE);
}
