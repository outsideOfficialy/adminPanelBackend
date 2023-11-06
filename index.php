<?php

include("./functions.php");

echo "<pre>";

switch ($_SERVER["REQUEST_METHOD"]) {
  case "GET": {
      $page = $_GET["page"];
      $id = $_GET["id"];
      $tableName = $config[$page]["tableName"];

      $db = connectToDB($db_path);

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
      $post = $_POST;
      $page = $post["page"];
      $db = connectToDB($db_path);

      if (!$db) {
        echo "Error connecting db";
        exit;
      }

      unset($post["page"]);

      $tableName = $config[$page]["tableName"];

      dbCreation($db, $page, $tableName);
      if ($page == "member_page") {
        $post["social_media_links"] = json_encode($post["social_media_links"]);
        $post["preview_picture"] = $dirToSaveImg . $_FILES["preview_picture"]["name"][0];
      }
      memberRecordCreate($db, $post, $tableName, $page);
      break;
    }
  case "PUT": {
      //! редактируем запись
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
      break;
    }
}
echo "</pre>";