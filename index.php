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

// if (
//   strpos($_SERVER["HTTP_REFERER"], "www.admin.outside-official.com") === false &&
//   ($_SERVER["REQUEST_METHOD"] === "DELETE" || $_SERVER["REQUEST_METHOD"] === "POST")
// ) {
//   http_response_code(403);
//   die("Access Denied!.");
// }

if ($req[0] == "") array_shift($req);

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
        // !редактирование записи....
        $recordId = $req[1];
        $dataToEdit = findByID($recordId, $config[$page]["tableName"], $db);

        $successDeletion;
        if (isset($dataToEdit["preview_picture"])) $successDeletion = deleteImg(json_decode($dataToEdit["preview_picture"]));
        if (isset($dataToEdit["preview_picture_mobile"])) $successDeletion = deleteImg(json_decode($dataToEdit["preview_picture_mobile"]));
        if (isset($dataToEdit["preview_picture_desktop"])) $successDeletion = deleteImg(json_decode($dataToEdit["preview_picture_desktop"]));

        if (!$successDeletion) {
          http_response_code(400);
          echo "Error deleting img!";
          exit;
        }

        if (!$dataToEdit) {
          http_response_code(404);
          echo "Record wasn't found!";
          exit;
        }

        if (!recordDelete($db, $recordId, $tableName)) {
          http_response_code(400);
          echo "Error with row deleting";
          exit;
        }
        addTimeToImg();

        foreach ($_FILES as $key => $val) {
          foreach ($val["name"] as $idx => $picName) {
            if ($picName !== "") {
              saveImg($_FILES);
              $dataToEdit[$key] = json_encode(addPathesToImgs($_FILES[$key]["name"]));
            }
          }
        }

        foreach ($post as $key => $val) {
          $dataToEdit[$key] = $post[$key];
        }

        if (!insertToTable($db, $tableName, $dataToEdit)) {
          http_response_code(400);
          echo "Error with record rewriting";
          exit;
        }
      }
      break;
    }
  case "DELETE": {
      // echo "Deleting $id of page $page in table $tableName";
      // http_response_code(200);
      // exit;

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

      $recordToDelete = findByID($id, $tableName, $db);

      print_r($recordToDelete);

      //exit;

      $successDeletion;
      if (isset($recordToDelete["preview_picture"])) $successDeletion = deleteImg(json_decode($recordToDelete["preview_picture"]));
      if (isset($recordToDelete["preview_picture_mobile"])) $successDeletion = deleteImg(json_decode($recordToDelete["preview_picture_mobile"]));
      if (isset($recordToDelete["preview_picture_desktop"])) $successDeletion = deleteImg(json_decode($recordToDelete["preview_picture_desktop"]));

      if (!$successDeletion) {
        http_response_code(400);
        echo "Error deleting img!";
        exit;
      }

      if (recordDelete($db, $id, $tableName)) {
        http_response_code(200);
        echo "Field with id:$id successfully deleted";
        exit;
      }
      if (deleteImg(json_decode($recordToDelete["preview_picture"]))) {
      } else {
        echo "error with deleting img";
        exit;
      }
      break;
    }
  default:
    http_response_code(404);
    echo "Method not found!";
}
