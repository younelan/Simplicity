<?php

namespace Opensitez\Simplicity\Plugins;

use \PDO;

//require __DIR__ . "/GalleryController.php";
//require_once(dirname(__FILE__) . "/GalleryGroupModel.php");

class Gallery extends \Opensitez\Simplicity\Plugin
{
    function get_menus($app = [])
    {
        $menus = [
            "content" => [
                "text" => "Content",
                "weight" => -2,
                "children" => [
                    "gallery" => ["plugin" => "gallery", "page" => "default", "text" => "Galleries", "category" => "all"],
                ],
                "visible" => true,
            ],

        ];
        return $menus;
    }
    function on_render_admin_page($app)
    {
        $page = $app['page'] ?? "list";
        $galleries = new \Opensitez\Plugins\GalleryController($this->config_object);
        //print(get_class($galleries));
        $galleries->set_handler($this->plugins);
        //$galleries->set_config($this->config);
        $galleries->connect();
        if ($_POST['gallery_id'] ?? false) {
            $galleryID = filter_input(INPUT_POST, 'gallery_id', FILTER_SANITIZE_STRING);
        } elseif ($_GET['gallery_id'] ?? false) {
            //print "ho";
            $galleryID = filter_input(INPUT_GET, 'gallery_id', FILTER_SANITIZE_STRING);
        } else {
            $galleryID = false;
        }
        //print $galleryID;exit;
        if ($_POST['photo_id'] ?? false) {
            $photoID = filter_input(INPUT_POST, 'photo_id', FILTER_SANITIZE_STRING);
        } elseif ($_GET['photo_id'] ?? false) {
            //print "ho";
            $photoID = filter_input(INPUT_GET, 'photo_id', FILTER_SANITIZE_STRING);
        } else {
            $photoID = false;
        }
        switch ($page) {
            case "add_gallery":
            case "update_gallery":
                return $galleries->addGallery($galleryID);
                break;
            case "view_gallery":
                return $galleries->renderGallery($galleryID);
            case "update_photo":
                return $galleries->editPhoto($photoID);
                break;
            case "photo_upload":
                return $galleries->uploadPhoto($galleryID);
                break;
            case "delete_gallery":
                return $galleries->deleteGallery($galeryID);
                break;
            case "upload":
                return $this->on_upload_page($app);
                break;
            default:
                return $galleries->listGalleries();
        }
    }
    function on_gallery_page($app)
    {
    }
    function on_upload_page($app)
    {
        $retval = "";
        if (!$this->config['user']) {
            $retval .= "This part requires a valid user";
            return $retval;
        }
        $DIR = __DIR__;
        $form_file = $DIR . "/views/uploadform.php";

        $form = file_get_contents($form_file);

        $retval .= $form;
        return $retval;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $uploadDir = __DIR__ . '/uploads/';
            $uploadedFiles = [];

            // Handle uploaded files
            if (!empty($_FILES['files']['name'][0])) {
                foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                    $fileName = $_FILES['files']['name'][$key];
                    $uploadPath = $uploadDir . $fileName;

                    if (move_uploaded_file($tmp_name, $uploadPath)) {
                        $uploadedFiles[] = $fileName;
                    }
                }
            }

            // Store file names in the database (you'll need to set up your database connection)
            // Example using PDO:
            if (!empty($uploadedFiles)) {
                $pdo = new PDO('mysql:host=localhost;dbname=your_db_name', 'username', 'password');
                foreach ($uploadedFiles as $fileName) {
                    $stmt = $pdo->prepare('INSERT INTO gallery (filename) VALUES (?)');
                    $stmt->execute([$fileName]);
                }
                echo 'Files uploaded successfully.';
            } else {
                echo 'No files were uploaded.';
            }
        }
        return $retval;
    }
}
