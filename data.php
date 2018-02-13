<?php
/**
 * Created by PhpStorm.
 * User: chris test
 * Date: 2/10/18
 * Time: 11:31 PM
 */

$mysqli = new mysqli("localhost", "root", "pass", "rapid");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
