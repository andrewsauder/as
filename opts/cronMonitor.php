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
		$cronFinish = json_decode( tools::easyCURL(['url'=>$_SESSION[AS_APP]['environment']['cron_monitor_url'].'jobHistory/end/'.$this->jobId.'/'.$this->runId]) );
	}

}