<?php


class cronMonitor {

	/** @var string */
	private $jobId;

	/** @var string */
	private $runId;

	public function __construct( $jobId, $autoStart=true ) {
		$this->jobId = $jobId;

		if($autoStart) {
			$this->start();
		}
	}

	public function start() {
		$cronMonStart = json_decode( tools::easyCURL(['url'=>$_SESSION[AS_APP]['environment']['cron_monitor_url'].'jobHistory/start/'.$this->jobId]) );
		$this->runId = $cronMonStart->data;
	}

	public function end() {
		$url = $_SESSION[AS_APP]['environment']['cron_monitor_url'].'jobHistory/end/'.$this->jobId;
		if( is_string($this->runId) ) {
			$url .= '/'.$this->runId;
		}
		$cronFinish = json_decode( tools::easyCURL(['url'=>$url]) );
	}

}