<?php

class ps_jahanpay extends ps_payment_gateway {
	public $api;

	public function send( $callback, $price, $username, $email, $order_id ) {
		$client = new ps_main_jahanpay();
		$txt    = urlencode( 'خرید از سایت با استفاده از افزونه پست شاپ' );
		$res    = $client->call( 'requestpayment', array( $this->api, $price, $callback, $order_id, $txt ) );
		$this->insert_payment( $username, $price, $order_id, $email );
		echo $this->info_alert( 'در حال اتصال به درگاه ...' );
		$this->redirect( "http://www.jahanpay.com/pay_invoice/{$res}" );
	}

	public function verify( $price, $post_id, $order_id, $course_id = 0 ) {
		if ( isset( $_GET['order_id'] ) && isset( $_GET["au"] ) ):
//			$orderId   = (int) $_GET["order_id"];
			$Refnumber = $_GET["au"];
			$client    = new ps_main_jahanpay();
			$result    = $client->call( 'verification', array( $this->api, $price, $_GET["au"] ) );
			if ( $result == 1 ) {
				$this->success_payment( $Refnumber, $order_id, $price, $post_id, $course_id );
			} else {
				echo $this->danger_alert( 'خطا در پردازش عملیات پرداخت ، نتیجه پرداخت : ' . $result );
			}
			$this->end_payment();
		endif;
	}
}


class ps_main_jahanpay {

	private $apiUrl = 'http://jahanpay.com/index.php/api', $timeOut = 30; //second
	protected $api = '';

	public function requestpayment( $api = 0, $amount = 0, $callback = '', $order_id = 0, $description = '', $bank = 'auto' ) {
		$data = array(
			'method'      => 'requestpayment',
			'api'         => $api,
			'amount'      => $amount,
			'order_id'    => $order_id,
			'description' => $description,
			'bank'        => $bank,
			'callback'    => $callback,
		);

		return $this->checkCUrl() ? $this->sendCurl( $data ) : $this->sendContent( $data );
	}

	public function verification( $api = 0, $amount = 0, $au = '' ) {
		$data = array(
			'method' => 'verification',
			'api'    => $api,
			'amount' => $amount,
			'au'     => $au,
		);

		return $this->checkCUrl() ? $this->sendCurl( $data ) : $this->sendContent( $data );
	}

	public function call( $method = '', array $arr = array() ) {
		if ( $method == 'requestpayment' ) {
			$_ = array();
			for ( $i = 0; $i < 6; $i ++ ) {
				$_[ $i ] = isset( $arr[ $i ] ) ? $arr[ $i ] : null;
			}

			return $this->requestpayment( $_[0], $_[1], $_[2], $_[3], $_[4], $_[5] );
		} else {
			$_ = array();
			for ( $i = 0; $i < 3; $i ++ ) {
				$_[ $i ] = isset( $arr[ $i ] ) ? $arr[ $i ] : null;
			}

			return $this->verification( $_[0], $_[1], $_[2] );
		}
	}

	private function checkCUrl() {
		if ( function_exists( 'curl_init' ) and extension_loaded( 'curl' ) ) {
			return true;
		}

		return false;
	}

	private function sendCurl( array $postdata = array() ) {
		$data = array();
		foreach ( $postdata as $key => $val ) {
			$data[] = "{$key}=" . urlencode( $val );
		}

		$do = curl_init();
		curl_setopt( $do, CURLOPT_URL, $this->apiUrl . '?' . join( '&', $data ) );
		curl_setopt( $do, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $do, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $do, CURLOPT_CONNECTTIMEOUT, $this->timeOut );
		$response = curl_exec( $do );
		curl_close( $do );

		return $response;
	}

	private function sendContent( array $postdata = array() ) {
		$data = array();
		foreach ( $postdata as $key => $val ) {
			$data[] = "{$key}=" . urlencode( $val );
		}

		return file_get_contents( $this->apiUrl . '?' . join( '&', $data ) );
	}

}
