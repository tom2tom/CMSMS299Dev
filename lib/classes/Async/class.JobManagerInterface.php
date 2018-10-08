<?php

namespace CMSMS\Async;

interface JobManagerInterface
{
	public function load_jobs_by_module($module);

	public function load_job_by_id(int $job_id);

	public function load_job(Job &$job):int;

	public function unload_jobs_by_module(string $module_name);

	public function unload_job_by_id(int $job_id);

	public function unload_job_by_name(string $module_name, string $job_name);

	public function unload_job(Job &$job);

	public function trigger_async_processing();

	public static function trigger_async_hook();
}
