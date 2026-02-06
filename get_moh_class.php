#!/usr/bin/php
<?php

	// AGI 스크립트 시작
	//require('phpagi.php'); // phpagi.php 파일이 Asterisk에서 제공되는 PHP AGI 라이브러리입니다.
	require('/var/lib/asterisk/agi-bin/phpagi.php'); // phpagi.php 파일이 Asterisk에서 제공되는 PHP AGI 라이브러리입니다.

	$agi = new AGI(); // AGI 객체 생성

	// MySQL 연결 정보
	$DB_HOST = "localhost";
	$DB_USER = "root";
	$DB_PASS = "logisoft123";
	$DB_NAME = "asterisk";

	$log_file = '/var/log/asterisk/get_moh_class.log';

	// 로그 기록 함수
	function log_message($message)
	{
		global $log_file;
		$timestamp = date('Y-m-d H:i:s');  // 타임스탬프
		$log_entry = "[$timestamp] $message\n";
		file_put_contents($log_file, $log_entry, FILE_APPEND);
	}

	// Asterisk에서 전달받은 착신번호
	//$exten = $agi->get_variable('EXTEN');
	$exten = $agi->get_variable('FROM_DID');

	// 만약 $exten이 배열이라면 첫 번째 값을 사용
	//if (is_array($exten))
	//{
		//$exten = $exten[0];  // 배열에서 첫 번째 값만 사용
	//}

	//var_dump($exten);
	// print_r()로 배열을 문자열로 변환하고 log_message에 전달
	log_message("EXTEN value: " . print_r($exten, true));

	// data 키의 값을 추출
	$exten_data = $exten['data'];

	// 로그 남기기
	log_message("Fetching MOH Class from Database for extension: $exten_data");
	$agi->verbose("Fetching MOH Class from Database for extension: $exten_data", 1);

	// MySQL 연결
	$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

	// MySQL 연결 오류 체크
	if ($mysqli->connect_error)
	{
		log_message("Database connection failed: " . $mysqli->connect_error);
		$agi->verbose("Database connection failed: " . $mysqli->connect_error, 1);
		$agi->set_variable("MOH_CLASS", "default");
		exit;
	}

	// 쿼리 실행

	$query = "SELECT moh_class FROM moh_settings WHERE did_number = ?";
	$stmt = $mysqli->prepare($query);
	$stmt->bind_param("s", $exten_data);
	$stmt->execute();
	$stmt->store_result();

	// 로그 메시지 형식 (현재 시간 포함)
	$log_message = "[" . date("Y-m-d H:i:s") . "] Executing query: " . $query . " with did_number: " . $exten_data . "\n";

	// 파일에 쿼리 로그 기록
	file_put_contents($log_file, $log_message, FILE_APPEND);

	// 결과 가져오기
	if ($stmt->num_rows > 0)
	{
		$stmt->bind_result($moh_class);
		$stmt->fetch();
		log_message("MOH Class found: $moh_class for extension: $exten_data");
	}
	else
	{
		// 기본값 설정
		$moh_class = "default";
		log_message("No MOH Class found for extension: $exten_data, using default");
	}

	// MOH 클래스 설정
	$agi->set_variable("MOH_CLASS", $moh_class);

	// 데이터베이스 연결 종료
	$stmt->close();
	$mysqli->close();

?>
