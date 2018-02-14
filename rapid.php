<?php
error_reporting(-1);
ini_set("display_errors", "on");

global $mysqli;
$mysqli = new mysqli("localhost", "root", "pass", "rapid");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

function config_set($config_file, $section, $key, $value)
{
    $config_data = parse_ini_file($config_file, true);
    $config_data[$section][$key] = $value;
    $new_content = '';
    foreach ($config_data as $section => $section_content) {
        $section_content = array_map(function ($value, $key) {
            return "$key=$value";
        }, array_values($section_content), array_keys($section_content));
        $section_content = implode("\n", $section_content);
        $new_content .= "[$section]\n$section_content\n";
    }
    file_put_contents($config_file, $new_content);
}

function nameok($name)
{
    global $mysqli;

    if ($stmt = $mysqli->prepare("SELECT NAME FROM SERVERS WHERE NAME = ?")) {
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->fetch();
        $stmt->close();
    }

    return is_null($result);
}

function create($name, $version = "latest")
{
    if (nameok($name)) {

        global $mysqli;
        if ($stmt = $mysqli->prepare("INSERT INTO SERVERS (NAME) VALUES (?)")) {
            $stmt->bind_param("s", $name);
            $stmt->execute();
        }
        $server_id = mysqli_insert_id($mysqli);
        echo $server_id;


        if ($version == "latest") {
            $ini_array = parse_ini_file("minerapid.ini", true);
            $version = $ini_array["minecraft"]["latestversion"];
        }
        $serverpath = "servers/" . $server_id;
        mkdir($serverpath, 0770);

        $jarname = "minecraft_server.". $version .".jar";
        copy("serverjars/" . $jarname, $serverpath ."/" .$jarname);

        $eula = fopen($serverpath ."/eula.txt", "a");
        fwrite($eula, "eula=true");
        return $server_id;

    }


}

function start($server_id){
    echo "<h1> server " .$server_id ." started</h1>";
    $files = scandir("servers/" .$server_id);
    $serverdir = "servers/" . $server_id;
    $jar = null;

    foreach($files as $file){
        if(strpos($file, "minecraft_server") === 0){
            $jar = $file;
            break;
        }
    }
    unlink($serverdir ."/screenlog.0");
    $screen_id = servertoken($server_id);
    echo "screen id is '".$screen_id ."'<br>";
    $command = "cd " .$serverdir ." && screen -L -dmS " .$screen_id ." java -Xms1024M -Xmx1024M -jar " .$jar ." nogui"; //spaces are very important turns out
    echo("command: " .$command ."<br>");
    exec($command,$output);
    foreach($output as $line)
        echo $line ."\n";

    exec("screen -ls", $output);
    print_r($output);
}

function delete($name){
    global $mysqli;
    if ($stmt = $mysqli->prepare("DELETE FROM SERVERS WHERE NAME = ?")) {
        $stmt->bind_param("i", $name);
        $stmt->execute();
    }
}

function servertoken($server_id){
    $id_array = str_split($server_id);
    $token = "";
    foreach($id_array as $number){

        $token .= chr(intval($number) + 65);
    }
    return $token;

}
function stop($server_id){
    command($server_id, "stop");
}
function forcestop($server_id){
    //for mac:
    $screenpid = explode(".",exec("screen -ls | grep " .servertoken($server_id) ." | awk '{print $1}'"))[0]; //at least I know what I'm doings
    $screenparent = exec("ps -el | grep -E " .$screenpid .".*login | grep -v grep | awk '{print $2}'");
    $javapid = exec("ps -el | grep -E " .$screenparent .".*java | grep -v grep | awk '{print $2}'");
    exec("kill -9 " .$javapid);
    echo "killed server " .$server_id ." at pid " .$javapid ." from screen pid " .$screenpid;
}

function command($server_id, $command){

    $command = "screen -S " .servertoken($server_id) ." -X stuff '" .$command ."'\r\n"; // \r\n is windows only? yeah right
    echo "executing command: " .$command;
    exec($command);
}



//create("foo");

if(isset($_GET["start"]))
start(17);

if(isset($_GET["fstop"]))
forcestop(17);

if(isset($_GET["stop"]))
    stop(17);


if(isset($_GET["command"]))
    if($_GET["command"][0] != "")
    command(17, $_GET["command"]);
//delete("foo");
?>

<form action="rapid.php" method="get">
    <h3> choose a button</h3>
    <input type="submit" name="start" value="start">
    <br>
    <input type="submit" name="stop" value="stop">
    <br>
    <input type="submit" name="fstop" value="force stop">

    <h3> enter a command</h3>
    <input type="text" name="command" placeholder="enter command here">
    <input type="submit" value="send command to server">
</form>


