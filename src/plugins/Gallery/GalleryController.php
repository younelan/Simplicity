<?php

namespace Opensitez\Simplicity\Plugins;

class GalleryController extends \Opensitez\Simplicity\Plugin
{
    private $galleryManager;

    public function GetAllParents($instance)
    {
        return get_class(instance) . '<-' .
            implode('<-', array_reverse(class_parents(instance)));
    }
    // public function __construct($config_object) {
    //     print($this->GetAllParents($this));
    // }
    public function connect()
    {
        //print_r($this->config);exit;
        $this->galleryManager = new \Opensitez\Plugins\GalleryGroupModel($this->config_object);
        $this->galleryManager->set_handler($this->framework);
        $this->galleryManager->connect();
    }

    public function deleteGallery($galleryID)
    {
        // Handle the deletion of a gallery.
        // Use POST method for this action.

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $deleteStatus = $this->galleryManager->deleteGallery($galleryID);

            if ($deleteStatus === true) {
                $params = [
                    'message' => 'Deleted Successfully',
                    'message_class' => 'alert alert-success'
                ];
                print_r($params);
                exit;
                return $this->listGalleries($params);
            } else {
                $params = [
                    'message' => 'Delete Failed',
                    'message_class' => 'alert alert-danger'
                ];

                return $this->listGalleries($params);
            }
        }
    }
    public function uploadPhoto($galleryID)
    {
        // Check if a file is uploaded
        //print "--1--\n";
        // Get the file details
        //return "<pre style='color:white'> ' $galleryID '\n" . print_r($_FILES, true);
        //print_r($_FILES);exit;
        if ($galleryID) {
            foreach ($_FILES['photo']['name'] as $idx => $fileName) {
                $current = [
                    'idx' => $idx,
                    'name' => $fileName,
                    'status' => $_FILES['photo']['error'][$idx],
                    'tmpname' => $_FILES['photo']['tmp_name'][$idx],
                    'fullpath' => $_FILES['photo']['full_path'][$idx],
                    'size' => $_FILES['photo']['size'][$idx],
                    'type' => $_FILES['photo']['type'][$idx],

                ];
                $fileTmpName = $current['tmpname'];
                if ($current['status'] === UPLOAD_ERR_OK) {
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $randomFilename = uniqid() . '.' . $extension;

                    // Save the file to a directory with the generated filename
                    $uploadDirectory = '/var/www/html/opensitez/local/uploads/';
                    $uploadedFilePath = $uploadDirectory . $randomFilename;

                    if (move_uploaded_file($fileTmpName, $uploadedFilePath)) {
                        // // print "--3--\n";
                        // print_r($galleryID);exit;
                        // File uploaded successfully, now add an entry to the photo table
                        $title = pathinfo($fileName, PATHINFO_FILENAME); // Use the photo title as the filename
                        $description = ''; // You can set a default description or retrieve it from the form

                        // Add entry to the photo table using the GalleryManager
                        $addPhotoStatus = $this->galleryManager->insertPhoto($galleryID, $title, $description, basename($uploadedFilePath));
                        //print_r("Add $title - $addPhotoStatus<br/>");
                        //print "--4--\n";

                        // Check if the addition was successful
                    } else {
                        return "uploaded file else";
                        // Failed to move the uploaded file, handle the error
                    }
                } else {
                    $errs[] = "Error on upload";
                }
            }
            if ($addPhotoStatus === true) {
                return $this->addGallery($galleryID);
                // print "--5--\n";
                // Redirect to a success page or display a success message
                return "success";
            } else {
                // Redirect to an error page or display an error message
                $data = "failed";
                return $this->addGallery($galleryID);
            }
        } else {
            return "Error, no valid gallery id provided";
        }

        // if($galleryID && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        //     print "--2--\n";
        //     exit;
        //     // Sanitize the gallery ID
        //     $galleryID = filter_var($galleryID, FILTER_SANITIZE_NUMBER_INT);
        //     $countfiles = count($_FILES['photo']['name']);


        //     $fileName = $_FILES['photo']['name'];
        //     $fileTmpName = $_FILES['photo']['tmp_name'];

        //     // Generate a random unique filename
        //     // print "$fileTmpName <br/>   $uploadedFilePath";
        //     // exit;

        // } else {
        //     print "error occured";
        //     // No file uploaded or an error occurred during upload, handle the error
        // }
        exit;
        return $this->addGallery($galleryID);
    }

    public function getPhotoList($galleryID)
    {
        $photos = $this->galleryManager->getGalleryPhotosByID($galleryID);
        //print_r($photos);exit;
        $galleryRows = [];
        foreach ($photos as $photo) {
            // Load the individual row template and replace placeholders
            //print_r($gallery);
            $rowTemplate = file_get_contents(__DIR__ . '/views/photo_row_template.html');

            $rowTemplate = $this->replacePlaceholders($rowTemplate, [
                'photo_name' => $photo['title'],
                'photo_id' => $photo['p_id'],
            ]);

            // Add the row to the array
            $galleryRows[] = $rowTemplate;
        }
        return implode("\n", $galleryRows);
    }
    public function editPhoto($photoID = null)
    {
        $data = [
            'message_class' => '',
            'message' => '',
            'button_label' => 'Update Photo',
            'form_title' => "Update Photo",
            'gallery_id' => '',
            'photo_title' => '',
            'photo_description' => '',

        ];

        if (!$photoID) {
            return "Valid Photo ID Required";
        }
        $photoData = $this->galleryManager->getPhotoByID($photoID);

        $title = $photoData['title'] ?? "";
        $description = $photoData['description'] ?? "";
        $galleryID = $photoData['g_id'] ?? "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //            print_r($_POST);
            // Handle the addition or update of a gallery.
            // Validate and sanitize user input
            $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
            if (!$photoID) {
                $photoID = filter_input(INPUT_POST, 'photo_id', FILTER_SANITIZE_STRING);
            }
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
            $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
            $slug = filter_input(INPUT_POST, 'slug', FILTER_SANITIZE_STRING);
            $status = "active";
            if ($photoID) {
                // It's an update operation
                $data['button_label'] = 'Update Photo';
                $data['form_title'] = 'Update Photo';
                $updateStatus = $this->galleryManager->updatePhoto($photoID, $title, $description, $status);

                if ($updateStatus) {
                    $data['message_class'] = 'alert alert-success';
                    $data['message'] = 'Photo updated successfully.';
                } else {
                    $data['message_class'] = 'alert alert-danger';
                    $data['message'] = 'Failed to update the Photo.';
                }
            } else {
            }
        }
        $data['photo_title'] = $title;
        $data['photo_description'] = $description;
        $data['form_action'] = "?plugin=gallery&page=update_photo&photo_id=$photoID";
        $data['gallery_link'] = "?plugin=gallery&page=update_gallery&gallery_id=$galleryID";

        return $this->renderForm(__DIR__ . '/views/common_photo_form.html', $data);
    }
    public function addGallery($galleryID, $app = [])
    {
        // Initialize data for rendering the form
        $data = [
            'message_class' => '',
            'message' => '',
            'button_label' => 'Add Gallery',
            'form_title' => "Add a Gallery",
            'gallery_title' => "",
            'gallery_description' => '',
            'photo_rows' => '',
            'gallery_id' => '',
            'title' => '',
            'description' => '',
        ];
        if ($app) {
            $data = array_replace($data, $app);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            print "--1--\n<br/>";
            //            print_r($_POST);
            // Handle the addition or update of a gallery.
            // Validate and sanitize user input
            $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
            if (!$galleryID) {
                $galleryID = filter_input(INPUT_POST, 'gallery_id', FILTER_SANITIZE_STRING);
            }
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
            $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
            $slug = filter_input(INPUT_POST, 'slug', FILTER_SANITIZE_STRING);

            // Perform additional validation as needed

            if (empty($title) || empty($description)) {
                // Handle form validation errors
                print "--2--\n<br/>";
                if ($galleryID) {
                    $galleryData = $this->galleryManager->getGalleryByID($galleryID);

                    if ($galleryData) {
                        $data['gallery_id'] = $galleryData['g_id'];
                        $data['form_title'] = $galleryData['title'];
                        $data['gallery_title'] = $galleryData['title'];
                        $data['gallery_description'] = $galleryData['description'];
                        $data['gallery_slug'] = $galleryData['gallery_slug'] ?? "";
                        $data['button_label'] = 'Update Gallery';
                        $data['photo_rows'] = $this->getPhotoList($galleryID);
                    }
                } else {
                    $data['message_class'] = 'alert alert-danger';
                    $data['message'] = 'Please fill in all fields.';
                }
            } else {
                //print "--3--\n<br/>";

                if ($galleryID) {
                    // It's an update operation
                    $data['button_label'] = 'Update Gallery';
                    $data['form_title'] = 'Update Gallery';
                    $data['photo_rows'] = $this->getPhotoList($galleryID);


                    $updateStatus = $this->galleryManager->updateGallery($galleryID, $title, $description, $status);
                    //print $updateStatus;exit;
                    if ($updateStatus) {
                        $data['message_class'] = 'alert alert-success';
                        $data['gallery_title'] = $title;
                        $data['gallery_description'] = $description;
                        $data['form_action'] = "?plugin=gallery&page=add_gallery&gallery_id=$galleryID";
                        $data['message'] = 'Gallery updated successfully.';
                    } else {
                        $data['message_class'] = 'alert alert-danger';
                        $data['gallery_title'] = $title;
                        $data['gallery_description'] = $description;
                        $data['message'] = 'Failed to update the gallery.';
                        $data['form_action'] = "?plugin=gallery&page=add_gallery&gallery_id=$galleryID";
                    }
                } else {
                    //print "--5--\n<br/>";

                    // It's an add operation
                    $insertStatus = $this->galleryManager->insertGallery($title, $description);
                    $data['gallery_title'] = $title;
                    $data['gallery_description'] = $description;
                    $data['gallery_slug'] = $galleryData['gallery_slug'] ?? "";
                    $data['photo_rows'] = $this->getPhotoList($galleryID);

                    if ($insertStatus) {
                        $data['message_class'] = 'alert alert-success';
                        $data['message'] = 'Gallery added successfully.';
                    } else {
                        $data['message_class'] = 'alert alert-danger';
                        $data['message'] = 'Failed to add the gallery.';
                        $data['gallery_title'] = $title;
                        $data['gallery_description'] = $description;
                        $data['gallery_slug'] = $galleryData['gallery_slug'] ?? "";
                        $data['form_action'] = "?plugin=gallery&page=add_gallery&gallery_id=$galleryID";
                    }
                }
            }
        }
        if ($galleryID) {
            // Prepopulate the form when updating a gallery
            $galleryData = $this->galleryManager->getGalleryByID($galleryID);

            if ($galleryData) {
                $data['gallery_id'] = $galleryData['g_id'];
                $data['form_title'] = $galleryData['title'];
                $data['gallery_title'] = $galleryData['title'];
                $data['gallery_description'] = $galleryData['description'];
                $data['gallery_slug'] = $galleryData['gallery_slug'] ?? "";
                $data['button_label'] = 'Update Gallery';
                $data['photo_rows'] = $this->getPhotoList($galleryID);
            }

            $data['form_action'] = "?plugin=gallery&page=add_gallery&gallery_id=$galleryID";
        } else {

            $data['form_title'] = "Add Gallery";
            $data['form_action'] = "?plugin=gallery&page=update_gallery&gallery_id=$galleryID";
        }

        // Render the common form with placeholders replaced
        return $this->renderForm(__DIR__ . '/views/common_gallery_form.html', $data);
    }
    public function renderGallery($galleryID)
    {
        // Retrieve gallery details
        $gallery = $this->galleryManager->getGalleryByID($galleryID);

        if ($gallery) {
            // Retrieve associated images for the gallery
            $photos = $this->galleryManager->getPhotosByGalleryID($galleryID);

            // Load the gallery template from file
            $template = file_get_contents(__DIR__ . '/views/gallery_slideshow_master.html');
            $data = [
                'title' => $gallery['title'],
                'description' => $gallery['description'],
                'gallery_id' => $galleryID,
                'slides' => $this->renderSlides($photos)
            ];
            //print_r($photos) ;exit;
            // Replace placeholders in the template with dynamic data
            $template = $this->replacePlaceholders($template, $data);

            // Return the populated template
            return $template;
        } else {
            // Return false if gallery not found
            return "Gallery not found";
        }
    }

    private function renderSlides($photos)
    {
        $slidesHtml = '';
        $pathprefix = '/local/uploads';
        $template = file_get_contents(__DIR__ . '/views/gallery_slideshow_slide.html');
        foreach ($photos as $index => $photo) {
            $slideClass = ($index === 0) ? 'active' : '';
            $fname = basename($photo['fname']);
            $data = [
                'title' => $photo['title'],
                'description' => $photo['description'],
                'file_path' => "$pathprefix/$fname",
                'active' => $slideClass,
            ];
            $slidesHtml .= $this->replacePlaceholders($template, $data);
        }
        return $slidesHtml;
    }


    public function listGalleries($params = [])
    {
        // Fetch a list of galleries from the GalleryManager
        $galleries = $this->galleryManager->listGalleries();
        $message = $params['message'] ?? "";

        $message_class = $params['message_class'] ?? "alert";
        if ($message) {
            $message = "        <div class='{$message_class}'>{$message}</div>";
        }
        //print $message;
        // Initialize an array to hold the rows
        $galleryRows = [];
        //print "hi";
        foreach ($galleries as $gallery) {
            // Load the individual row template and replace placeholders
            //print_r($gallery);
            $rowTemplate = file_get_contents(__DIR__ . '/views/gallery_row_template.html');

            $rowTemplate = $this->replacePlaceholders($rowTemplate, [
                'gallery_name' => $gallery['title'],
                'gallery_id' => $gallery['g_id'],
            ]);

            // Add the row to the array
            $galleryRows[] = $rowTemplate;
        }


        // Load the master table template and replace the {rows} placeholder
        $masterTemplate = file_get_contents(__DIR__ . '/views/gallery_master_template.html');

        $masterTemplate = $this->replacePlaceholders($masterTemplate, [
            'rows' => implode('', $galleryRows),
            'message' => $message,
            'add_link' => "?plugin=gallery&page=add_gallery",
        ]);

        return $masterTemplate;
    }

    private function replacePlaceholders($content, $data)
    {
        foreach ($data as $key => $value) {
            $placeholder = '{' . $key . '}';
            $content = str_replace($placeholder, $value, $content);
        }
        return $content;
    }
    // Function to render the form with placeholders replaced
    private function renderForm($formFile, $data)
    {
        // Get the form content from an external file
        $formContent = file_get_contents($formFile);

        // Replace placeholders dynamically
        $formContent = $this->replacePlaceholders($formContent, $data);

        return $formContent;
    }
}
