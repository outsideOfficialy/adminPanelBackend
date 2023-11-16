<?php

// TODO разобраться с PARAM
// TODO написать уже так, используя функции, в будущем перенести все на классы


//! ------------------------VARIABLES------------------------
$db_path = "dataBase.db";

$dirToSaveImg = "img/";


$config = array(
  "music" => array(
    "tableName" => "music",
    "jsonName" => "music.json",
    "columnSearch" => "release_name"
  ),
  "news" => array(
    "tableName" => "news",
    "jsonName" => "news.json",
    "columnSearch" => "title"
  ),
  "merch" => array(
    "tableName" => "merch",
    "jsonName" => "merch.json",
    "columnSearch" => "title"
  ),
  "members" => array(
    "tableName" => "members",
    "jsonName" => "members.json",
    "columnSearch" => "nickname"
  ),
);

//! ---------------------------------------------------------
// генерирует id для записей
function idGenerator()
{
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $id = '';

  for ($i = 0; $i < 8; $i++) {
    $id .= $characters[rand(0, strlen($characters) - 1)];
  }

  return $id;
}

function connectToDB($dbName)
{
  global $db_path;

  if (file_exists(__DIR__ . "\\" . $dbName)) {
    $db = new SQLite3($db_path);
    return $db;
  } else {
    return null;
  }
}

// получаем всю базу данных определенной таблицы
function getAllDb($db, $tableName)
{
  $data = array();

  $res = $db->query("SELECT * FROM $tableName");

  while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $data[] = $row;
  }

  return $data;
}

// поиск строки по айдишнику
function findByID($id, $tableName, $db)
{
  $sql = "SELECT * FROM $tableName WHERE id = :id";

  $stmt = $db->prepare($sql);
  $stmt->bindValue(':id', $id, SQLITE3_TEXT);

  $result = $stmt->execute();

  if ($result) {
    return $result->fetchArray(SQLITE3_ASSOC);
  }

  return false;
}

// поиск по определенной строке 
function findByString($str, $tableName, $db, $columnName)
{
  $sql = "SELECT * FROM $tableName WHERE $columnName LIKE :search_string";
  $stmt = $db->prepare($sql);
  $stmt->bindValue(':search_string', '%' . $str . '%', SQLITE3_TEXT);

  $result = $stmt->execute();
  $data = [];

  while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $data[] = $row;
  }

  return empty($data) ? false : $data;
}

// вставка в таблицу строки данных
function insertToTable($db, $tableName, $data)
{
  $sql = "INSERT INTO $tableName (" . implode(", ", array_keys($data)) . ") VALUES (" . implode(", ", array_fill(0, count($data), "?")) . ")";

  $stmt = $db->prepare($sql);

  $i = 1;
  foreach ($data as $value) {
    $stmt->bindValue($i++, $value, SQLITE3_TEXT);
  }

  $result = $stmt->execute();

  return $result ? true : false;
}

// сохранение картинок
function saveImg($files)
{
  global $dirToSaveImg;

  foreach ($files["preview_picture"]["name"] as $key => $filename) {
    $uploadFile = $dirToSaveImg . basename($filename);
    if (!move_uploaded_file($files["preview_picture"]["tmp_name"][$key], $uploadFile)) return false;
  }
  return true;
}

// получить все значения одной колонки из таблицы
function getColumnValues($db, $tableName, $columnName)
{
  $query = "SELECT $columnName FROM $tableName";
  $result = $db->query($query);
  return $result ? $result : false;
}

// передаю сюда локальный путь
function deleteFile($path)
{
  if (file_exists(__DIR__ . "\\" . $path)) {
    return unlink(__DIR__ . "\\" . $path) ? true : false;
  }
}

function saveTableToJson($tableName, $db, $jsonName)
{
  if (file_exists($jsonName)) {
    file_put_contents($jsonName, json_encode(getAllDb($db, $tableName)));
  } else {
    http_response_code(404);
    echo "Json file to save data not found";
  }
}

function memberRecordCreate($db, $post, $tableName, $page)
{
  global $config;
  //! создаем запись
  $newID = "";

  $currentTime = time();
  do {
    $newID = idGenerator();

    if (time() >= $currentTime + 3) {
      http_response_code(500);
      echo "Failed to generate ID of record";
      exit;
    }
  } while (findByID($newID, $tableName, $db));
  $post["id"] = $newID;


  if (!saveImg($_FILES)) {
    http_response_code(400);
    echo "Error saving img!";
    exit;
  }

  if (!insertToTable($db, $tableName, $post)) {
    http_response_code(400);
    echo "Failed to insert into table!";
    exit;
  }

  saveTableToJson($config[$page]["tableName"], $db, $config[$page]["jsonName"]);
}

function dbCreation($db, $page, $tableName)
{

  $fields = "";

  switch ($page) {
    case "music":
      $fields = "id TEXT PRIMARY KEY,
          album TEXT,
          release_name TEXT,
          release_songs TEXT,
          preview_picture TEXT,
          social_media_links TEXT,
          send_later TEXT";
      break;
    case "news":
      $fields = "id TEXT PRIMARY KEY,
          title TEXT,
          subtitle TEXT,
          content TEXT,
          preview_picture TEXT,
          send_later TEXT";
      break;
    case "merch":
      $fields = "id TEXT PRIMARY KEY,
          title TEXT,
          description TEXT,
          content TEXT,
          pictures TEXT,
          price TEXT,
          send_later TEXT";
      break;
    case "members":
      $fields = "id TEXT PRIMARY KEY,
          nickname TEXT,
          birthdate TEXT,
          role TEXT,
          about TEXT,
          social_media_links TEXT,
          preview_picture TEXT,
          send_later TEXT";
      break;
    default:
      http_response_code(400);
      echo "Error with creation db";
      exit;
  }

  $db->exec("CREATE TABLE IF NOT EXISTS $tableName ($fields);");
}
