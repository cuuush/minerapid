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

    $files = scandir("servers/" .$server_id);
    $jar = null;

    foreach($files as $file){
        if(strpos($file, "minecraft_server") === 0){
            $jar = $file;
            break;
        }
    }

    $command = "screen -d -m -S " .$server_id ." java -Xms1024M -Xmx1024M -jar" .$jar ."nogui";
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


//create("foo");
start(17);
//delete("foo");


?>