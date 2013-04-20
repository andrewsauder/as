<?php
include_once(AS__PATH.'libs/PHPMailer_v5-1/class.phpmailer.php');
class email {

	private static $from = 'itstaff@garrettcounty.org';

	private static $sep;
	private static $images = NULL;
	private static $attachments = array();
	private static $extradata = array();
	private static $mailplugin = NULL;

	private static function headers() {
		$headers = array();
		$headers[] = "From: " . self::$from;
		$headers[] = "X-Mailer: PHP/" . phpversion();
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "Content-Type: multipart/related;boundary=\"sep-{".self::$sep."}\"";//"Content-Type: text/html; charset=ISO-8859-1";

		$glue = "\r\n";

		return implode( $glue, $headers );
	}

	public static function images($attachments) {

		self::$images = array();

		foreach($attachments as $i=>$attachment) {

			$path = dirname(__FILE__)."/../../../www/theme/".sessionController::getTheme()."/images/email/".$attachment;

			if (file_exists($path)) {
				//get the mime type
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$type = finfo_file($finfo, $path);
				finfo_close($finfo);
			}

			self::$images[] = array(
				'path'=>$path,
				'name'=>$attachment,
				'contents'=>chunk_split(base64_encode( file_get_contents($path))),
				'cid'=>'cid'.$i,
				'type'=>$type
			);
		}

	}

	public static function attachments($attachments) {

		self::$attachments = array();

		foreach($attachments as $attachmentPath) {

			self::$attachments[] = $attachmentPath;
		}

	}

	public static function send($to, $subject, $message, $from='') {

		if(trim($from)=='') {
			$from = 'itstaff@garrettcounty.org';
		}

		self::$mailplugin = new PHPMailer(true);

		try {
			if(is_string($to)) {
				self::$mailplugin->AddAddress($to);
			}
			elseif(is_array($to)) {
				foreach($to as $addTo) {
					self::$mailplugin->AddAddress($addTo);
				}
			}
			self::$mailplugin->SetFrom($from, 'Garrett County Government');
			self::$mailplugin->Subject = $subject;
			self::$mailplugin->MsgHTML($message);

			if(is_array(self::$images)) {
				foreach(self::$images as $image) {
					self::$mailplugin->AddEmbeddedImage($image['path'],$image['cid'],$image['name'],'base64',$image['type']);
				}
			}

			if(is_array(self::$attachments)) {
				foreach(self::$attachments as $attachment) {
					self::$mailplugin->AddAttachment($attachment);
				}
			}

			self::$mailplugin->Send();

		}
		catch(phpmailerException $e) {
			error_log('MAIL: PHPMailer library exception - failed sending to '. $to.' with subject line '.$subject);
			return false;
		}
		catch(Exception $e) {
			error_log('MAIL: Generic exception - failed sending to '. $to.' with subject line '.$subject);
			return false;
		}

	}

}

?>