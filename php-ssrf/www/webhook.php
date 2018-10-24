<?php
  function webhook($url, $title) {
    $curl = curl_init();

    $data = ['title' => $title];

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);

    $response = curl_exec($curl);

    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);

		curl_close($curl);

		return ['code' => $code, 'header' => $header, 'body' => $body];
	}
?>
