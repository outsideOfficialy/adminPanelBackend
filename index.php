<?php

include("./functions.php");

// echo "<pre>";

/**
 * music
 * news
 * merch
 * members
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

      echo json_encode($data);
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
      print_r($post);
      dbCreation($db, $page, $tableName);

      if ($post["id"] === "") {
        recordCreate($db, $post, $tableName, $page);
      } else {
        // !редактирование записи....
        if ($page == "member_page") {
          $post["social_media_links"] = json_encode($post["social_media_links"]);
        }
        $post["preview_picture"] = $dirToSaveImg . $_FILES["preview_picture"]["name"];
        $imgName = $post["preview_picture"];
        $memberInfo = findByID($post["id"], $tableName, $db);
        if (!$memberInfo) {
          http_response_code(404);
          echo "Member not found";
          exit;
        }
        if (!saveImg($_FILES)) {
          http_response_code(400);
          echo "Error saving img!";
          exit;
        }
        if (!insertToTable(
          $db,
          $tableName,
          $post
        )) {
          http_response_code(400);
          echo "Failed to insert into table!";
          exit;
        }
      }
      break;
    }
  case "DELETE": {

      break;
    }
  default:
    http_response_code(404);
    echo "Method not found!";
}
// echo "</pre>";