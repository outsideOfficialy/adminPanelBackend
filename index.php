<?php

include("./functions.php");

// echo "<pre>";

/**
 * music
 * news
 * merch
 * members
 * slide
 */

$url = $_GET["url"];
$req = explode("/", $url);


if (!sizeof($req)) {
  http_response_code(400);
  echo "Missing arguments in path";
  exit;
}

switch ($_SERVER["REQUEST_METHOD"]) {
  case "GET": {
      if (sizeof($req) !== 2) {
        http_response_code(400);
        echo "Missing arguments in path";
        exit;
      }

      $page = $req[0];
      $id = $req[1];
      $tableName = $config[$page]["tableName"];
      $db = connectToDB($db_path);

      if (!$db) {
        http_response_code(400);
        echo "Error connecting db!";
        exit;
      }

      $data = findByID($id, $tableName, $db);

      // если не удалось найти по айди
      if (!$data) {
        $data = findByString($id, $tableName, $db, $config[$page]["columnSearch"]);

        // если не удалось найти по строке
        if (!$data) {
          http_response_code(404);
          echo "Item not found";
          exit;
        }

        echo json_encode($data);
        exit;
      }

      echo json_encode(array($data));
      break;
    }
  case "POST": {
      // тут уже смотреть, есть айдишник или нету
      $post = $_POST;
      $page = $req[0];
      $db = connectToDB($db_path);

      if (!$db) {
        echo "Error connecting db";
        exit;
      }

      $tableName = $config[$page]["tableName"];
      if (isset($post["release_songs"])) $post["release_songs"] = json_encode($post["release_songs"]);
      if (isset($post["social_media_links"])) {
        $post["social_media_links"] = json_encode($post["social_media_links"]);
      }
      if (!isset($post["send_later"]) || $post["send_later"] == "") $post["send_later"] = "-";
      dbCreation($db, $page, $tableName);

      if ($post["id"] === "") {
        recordCreate($db, $post, $tableName, $page);
      } else {
        exit;
        // !редактирование записи....
        
      }
      break;
    }
  case "DELETE": {
      echo "Deleting $id of page $page in table $tableName";
      http_response_code(200);
      exit;

      if (sizeof($req) !== 2) {
        http_response_code(400);
        echo "Missing arguments in path";
        exit;
      }
      $page = $req[0];
      $id = $req[1];
      $tableName = $config[$page]["tableName"];
      $db = connectToDB($db_path);

      if (!$db) {
        http_response_code(400);
        echo "Error connecting db!";
        exit;
      }

      $query = "DELETE FROM $tableName WHERE id = :id";
      $stmt = $db->prepare($query);
      $stmt->bindParam(':id', $id, SQLITE3_INTEGER);
      $stmt->execute();

      if ($stmt) {
        http_response_code(200);
        echo "Field with id:$id successfully deleted";
        exit;
      } else {
        http_response_code(400);
        echo "Ошибка удаления строки.";
        exit;
      }
      break;
    }
  default:
    http_response_code(404);
    echo "Method not found!";
}