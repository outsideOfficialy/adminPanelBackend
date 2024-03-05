<?php

// TODO разобраться с PARAM
// TODO написать уже так, используя функции, в будущем перенести все на классы


//! ------------------------VARIABLES------------------------
$db_path = "dataBase.db";

// обязательно указывать после конечной папки слеш
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
  "slider" => array(
    "tableName" => "slider",
    "jsonName" => "slider.json",
    "columnSearch" => "title"
  )
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
  if (file_exists($dbName)) {
    $db = new SQLite3($dbName);
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

  foreach ($files as $file_field) {

    $fileAmnt = sizeof($file_field["name"]);
    for ($i = 0; $i < $fileAmnt; $i += 1) {
      $uploadFile = $dirToSaveImg . basename($file_field["name"][$i]);
      if (!move_uploaded_file($file_field["tmp_name"][$i], $uploadFile)) return false;
    }
  }
  return true;
}

function addPathesToImgs($imgArr)
{
  global $dirToSaveImg;
  $newArr = array();

  foreach ($imgArr as $key => $val) {
    $newArr[] = $dirToSaveImg . $val;
  }

  return $newArr;
}

//! ВНИМАНИЕ!!! Функция ищет по названию файла в папке, которая указана в $dirToSaveImg, то что по факту
//! записано в бд - все равно, это обрежеться и возьмется только название файла
function deleteImg($picArray)
{
  global $dirToSaveImg;

  foreach ($picArray as $idx => $picName) {
    $slash = mb_strrpos($picName, "/");

    try {
      // если в пути содержится слеш, то есть файл в какой-то папке
      if ($slash !== false) {
        $fileName = substr($picName, $slash + 1, strlen($picName) - 1);
  
        $filePath = $dirToSaveImg . $fileName;
        if (file_exists($filePath)) {
          if (!unlink($filePath)) {
            http_response_code(400);
            echo "Error with deleting img";
            return false;
          }
        } else {
          http_response_code(404);
          echo "File not found";
          return false;
        }
      } //если не дай бог файл вне папки, а на корню с проектом
      else {
        if (file_exists($picName)) {
          if (!unlink($picName)) {
            http_response_code(400);
            echo "Error with deleting img";
          }
        }
      }
    } catch(Exception $e) {
      echo $e;
    }
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

function addTimeToImg()
{
  $idx = 0;
  foreach ($_FILES as $field_name => $file_field) {
    $fileAmnt = sizeof($file_field["name"]);
    if ($file_field["name"][0] == "") return;
    for ($i = 0; $i < $fileAmnt; $i += 1) {
      $_FILES[$field_name]["name"][$i] = time() . $idx . "_" . $_FILES[$field_name]["name"][$i];
    }
    $idx++;
  }
}

function recordCreate($db, $post, $tableName, $page)
{
  global $config, $dirToSaveImg;
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


  addTimeToImg();

  if (!saveImg($_FILES)) {
    http_response_code(400);
    echo "Error saving img!";
    exit;
  }

  // записываем пути к картинкам
  foreach ($_FILES as $key => $file_field) {
    $files = array();
    $fileAmnt = sizeof($file_field["name"]);

    for ($i = 0; $i < $fileAmnt; $i += 1) {
      $uploadFilePath = $dirToSaveImg . basename($file_field["name"][$i]);
      $files[] = $uploadFilePath;
    }
    $post[$key] = json_encode($files);
  }

  if (!insertToTable($db, $tableName, $post)) {
    http_response_code(400);
    echo "Failed to insert into table!";
    echo "\n" . $db->lastErrorMsg();
    exit;
  }

  saveTableToJson($config[$page]["tableName"], $db, $config[$page]["jsonName"]);
}

function recordDelete($db, $id, $tableName)
{
  $id = SQLite3::escapeString($id);

  $stmt = $db->prepare("DELETE FROM $tableName WHERE id = :id");
  $stmt->bindValue(':id', $id, SQLITE3_TEXT);
  $result = $stmt->execute();

  $allDb = getAllDb($db, $tableName);

  return $result;
}

function dbCreation($db, $page, $tableName)
{

  $fields = "";

  switch ($page) {
    case "music":
      $fields = "id TEXT PRIMARY KEY,
          music_type TEXT,
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
          preview_picture TEXT,
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
    case "slider":
      $fields = "id TEXT PRIMARY KEY,
          title TEXT,
          link TEXT,
          preview_picture_mobile TEXT,
          preview_picture_desktop TEXT,
          send_later TEXT";
      break;
    default:
      http_response_code(400);
      echo "Error with creation db";
      exit;
  }

  $db->exec("CREATE TABLE IF NOT EXISTS $tableName ($fields);");
}
