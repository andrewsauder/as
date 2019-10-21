<?php


class cronMonitor {

	/** @var string */
	private $jobId;

	/** @var string */
	private $runId;

	public function __construct( $jobId ) {
		$this->jobId = $jobId;
	}

	public function start() {
		$cronMonStart = json_decode( tools::easyCURL(['url'=>$_SESSION[AS_APP]['environment']['cron_monitor_url'].'jobHistory/start/'.$this->jobId]) );
		$this->runId = $cronMonStart->data;
	}

	public function end() {
		$cronFinish = json_decode( tools::easyCURL(['url'=>$_SESSION[AS_APP]['environment']['cron_monitor_url'].'jobHistory/end/'.$this->jobId.'/'.$this->runId]) );
	}

}