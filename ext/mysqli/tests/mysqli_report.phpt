--TEST--
mysqli_report()
--SKIPIF--
<?php 
require_once('skipif.inc');
require_once('skipifemb.inc'); 
require_once('skipifconnectfailure.inc');
?>
--FILE--
<?php
	include "connect.inc";

	$tmp    = NULL;
	$link   = NULL;

	if (NULL !== ($tmp = @mysqli_report()))
		printf("[001] Expecting NULL, got %s/%s\n", gettype($tmp), $tmp);

	if (true !== ($tmp = mysqli_report(-1)))
		printf("[002] Expecting boolean/true even for invalid flags, got %s/%s\n", gettype($tmp), $tmp);

	if (true !== ($tmp = mysqli_report(MYSQLI_REPORT_ERROR)))
		printf("[003] Expecting boolean/true, got %s/%s\n", gettype($tmp), $tmp);

	if (true !== ($tmp = mysqli_report(MYSQLI_REPORT_STRICT)))
		printf("[004] Expecting boolean/true, got %s/%s\n", gettype($tmp), $tmp);

	if (true !== ($tmp = mysqli_report(MYSQLI_REPORT_INDEX)))
		printf("[005] Expecting boolean/true, got %s/%s\n", gettype($tmp), $tmp);

	if (true !== ($tmp = mysqli_report(MYSQLI_REPORT_ALL)))
		printf("[007] Expecting boolean/true, got %s/%s\n", gettype($tmp), $tmp);

	if (true !== ($tmp = mysqli_report(MYSQLI_REPORT_OFF)))
		printf("[008] Expecting boolean/true, got %s/%s\n", gettype($tmp), $tmp);

	require('table.inc');

	/*
	Internal macro MYSQL_REPORT_ERROR
	*/
	mysqli_report(MYSQLI_REPORT_ERROR);

	mysqli_multi_query($link, "BAR; FOO;");
	mysqli_query($link, "FOO");
	/* This might work if you accept anonymous users in your setup */
	mysqli_change_user($link, "0123456789-10-456789-20-456789-30-456789-40-456789-50-456789-60-456789-70-456789-80-456789-90-456789", "password", $db);
	mysqli_kill($link, -1);

	// mysqli_ping() cannot be tested, because one would need to cause an error inside the C function to test it
	mysqli_prepare($link, "FOO");
	mysqli_real_query($link, "FOO");
	if (@mysqli_select_db($link, "Oh lord, let this be an unknown database name"))
		printf("[009] select_db should have failed\n");
	// mysqli_store_result() and mysqli_use_result() cannot be tested, because one would need to cause an error inside the C function to test it


	// Check that none of the above would have caused any error messages if MYSQL_REPORT_ERROR would
	// not have been set. If that would be the case, the test would be broken.
	mysqli_report(MYSQLI_REPORT_OFF);

	mysqli_multi_query($link, "BAR; FOO;");
	mysqli_query($link, "FOO");
	mysqli_change_user($link, "This might work if you accept anonymous users in your setup", "password", $db);
	mysqli_kill($link, -1);
	mysqli_prepare($link, "FOO");
	mysqli_real_query($link, "FOO");
	mysqli_select_db($link, "Oh lord, let this be an unknown database name");

	/*
	Internal macro MYSQL_REPORT_STMT_ERROR
	*/

	mysqli_report(MYSQLI_REPORT_ERROR);

	$stmt = mysqli_stmt_init($link);
	mysqli_stmt_prepare($stmt, "FOO");

	$stmt = mysqli_stmt_init($link);
	mysqli_stmt_prepare($stmt, "SELECT id FROM test WHERE id > ?");
	$id = 1;
	mysqli_kill($link, mysqli_thread_id($link));
	mysqli_stmt_bind_param($stmt, "i", $id);
	mysqli_stmt_close($stmt);
	mysqli_close($link);

	/* mysqli_stmt_execute() = mysql_stmt_execute cannot be tested from PHP */
	if (!$link = mysqli_connect($host, $user, $passwd, $db, $port, $socket))
			printf("[008] [%d] %s\n", mysqli_connect_errno(), mysqli_connect_error());
	$stmt = mysqli_stmt_init($link);
	mysqli_stmt_prepare($stmt, "SELECT id FROM test WHERE id > ?");
	$id = 1;
	mysqli_stmt_bind_param($stmt, "i", $id);
	// mysqli_kill($link, mysqli_thread_id($link));
	mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	mysqli_close($link);

	/* mysqli_kill() "trick" does not work for any of the following because of an E_COMMANDS_OUT_OF_SYNC */
	/* mysqli_stmt_bind_result() = mysql_stmt_bind_result() cannot be tested from PHP */
	/* mysqli_stmt_fetch() = mysql_stmt_fetch() cannot be tested from PHP */
	/* mysqli_stmt_result_metadata() = mysql_stmt_result_metadata() cannot be tested from PHP */
	/* mysqli_stmt_store_result() = mysql_stmt_store_result() cannot be tested from PHP */

	// Check
	mysqli_report(MYSQLI_REPORT_OFF);

	if (!$link = mysqli_connect($host, $user, $passwd, $db, $port, $socket))
		printf("[010] [%d] %s\n", mysqli_connect_errno(), mysqli_connect_error());
	$stmt = mysqli_stmt_init($link);
	mysqli_stmt_prepare($stmt, "FOO");

	$stmt = mysqli_stmt_init($link);
	mysqli_stmt_prepare($stmt, "SELECT id FROM test WHERE id > ?");
	$id = 1;
	mysqli_kill($link, mysqli_thread_id($link));
	mysqli_stmt_bind_param($stmt, "i", $id);
	mysqli_stmt_close($stmt);
	mysqli_close($link);

	if (!$link = mysqli_connect($host, $user, $passwd, $db, $port, $socket))
		printf("[011] [%d] %s\n", mysqli_connect_errno(), mysqli_connect_error());
	$stmt = mysqli_stmt_init($link);
	mysqli_stmt_prepare($stmt, "SELECT id FROM test WHERE id > ?");
	$id = 1;
	mysqli_stmt_bind_param($stmt, "i", $id);
	mysqli_kill($link, mysqli_thread_id($link));
	mysqli_stmt_execute($stmt);
	mysqli_stmt_close($stmt);
	mysqli_close($link);

	/*
	MYSQLI_REPORT_STRICT

	MYSQLI_REPORT_STRICT --->
	php_mysqli_report_error() ->
		MYSQLI_REPORT_MYSQL_ERROR,
		MYSQLI_REPORT_STMT_ERROR ->
			already tested

	php_mysqli_throw_sql_exception() ->
		mysqli_real_connect()
		mysqli_connect()

	can't be tested: mysqli_query() via mysql_use_result()/mysql_store_result()
	*/
	mysqli_report(MYSQLI_REPORT_OFF);
	mysqli_report(MYSQLI_REPORT_STRICT);

	try {

		if ($link = mysqli_connect($host, $user . 'unknown_really', $passwd . 'non_empty', $db, $port, $socket))
			printf("[012] Can connect to the server using host=%s, user=%s, passwd=***non_empty, dbname=%s, port=%s, socket=%s\n",
				$host, $user . 'unknown_really', $db, $port, $socket);
		mysqli_close($link);

	} catch (mysqli_sql_exception $e) {
		printf("[013] %s\n", $e->getMessage());
	}

	try {
		if (!$link = mysqli_init())
			printf("[014] [%d] %s\n", mysqli_connect_errno(), mysqli_connect_error());

		if ($link = mysqli_real_connect($link, $host, $user . 'unknown_really', $passwd . 'non_empty', $db, $port, $socket))
			printf("[015] Can connect to the server using host=%s, user=%s, passwd=***non_empty, dbname=%s, port=%s, socket=%s\n",
				$host, $user . 'unknown_really', $db, $port, $socket);
		mysqli_close($link);
	} catch (mysqli_sql_exception $e) {
		printf("[016] %s\n", $e->getMessage());
	}

	/*
	MYSQLI_REPORT_INDEX --->
	mysqli_query()
	mysqli_stmt_execute()
	mysqli_prepare()
	mysqli_real_query()
	mysqli_store_result()
	mysqli_use_result()

	No test, because of to many prerequisites:
		- Server needs to be started with and
			--log-slow-queries --log-queries-not-using-indexes
		- query must cause the warning on all MySQL versions

	TODO:
	*/
	$log_slow_queries = false;
	$log_queries_not_using_indexes = false;

	if (!$link = mysqli_connect($host, $user, $passwd, $db, $port, $socket))
		printf("[017] [%d] %s\n", mysqli_connect_errno(), mysqli_connect_error());

	if (!$res = mysqli_query($link, "SHOW VARIABLES LIKE 'log_slow_queries'"))
		printf("[018] [%d] %s\n", mysqli_errno($link), mysqli_error($link));

	if (!$row = mysqli_fetch_assoc($res))
		printf("[019] [%d] %s\n", mysqli_errno($link), mysqli_error($link));

	$log_slow_query = ('ON' == $row['Value']);

	if (mysqli_get_server_version($link) >= 51011) {
		if (!$res = mysqli_query($link, "SHOW VARIABLES LIKE 'log_queries_not_using_indexes'"))
			printf("[020] [%d] %s\n", mysqli_errno($link), mysqli_error($link));

		if (!$row = mysqli_fetch_assoc($res))
			printf("[021] [%d] %s\n", mysqli_errno($link), mysqli_error($link));

		$log_queries_not_using_indexes = ('ON' == $row['Value']);

		if ($log_slow_queries && $log_queries_not_using_indexes) {

			for ($i = 100; $i < 20000; $i++) {
				if (!mysqli_query($link, "INSERT INTO test(id, label) VALUES ($i, 'z')"))
					printf("[022 - %d] [%d] %s\n", $i - 99, mysqli_errno($link), mysqli_error($link));
			}

			if (!$res = @mysqli_query($link, "SELECT id, label FROM test WHERE id = 1323"))
				printf("[023] [%d] %s\n", mysqli_errno($link), mysqli_error($link));

			mysqli_free_result($res);

			mysqli_report(MYSQLI_REPORT_OFF);
			mysqli_report(MYSQLI_REPORT_INDEX);
		}
	}

	print "done!";
?>
--EXPECTF--
Warning: mysqli_multi_query(): (%d/%d): You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'BAR; FOO' at line 1 in %s on line %d

Warning: mysqli_query(): (%d/%d): You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FOO' at line 1 in %s on line %d

Warning: mysqli_change_user(): (%d/%d): Access denied for user '0123456789-10-456789-20-456789-%s'@'%s' (using password: YES) in %s on line %d

Warning: mysqli_kill(): processid should have positive value in %s on line %d

Warning: mysqli_prepare(): (%d/%d): You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FOO' at line 1 in %s on line %d

Warning: mysqli_real_query(): (%d/%d): You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FOO' at line 1 in %s on line %d

Warning: mysqli_kill(): processid should have positive value in %s on line %d

Warning: mysqli_stmt_prepare(): (%d/%d): You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near 'FOO' at line 1 in %s on line %d
[013] Access denied for user '%s'@'%s' (using password: YES)
[016] Access denied for user '%s'@'%s' (using password: YES)
done!