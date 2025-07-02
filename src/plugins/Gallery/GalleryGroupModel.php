<?php
namespace Opensitez\Plugins;

use \PDO;

class GalleryGroupModel extends \Opensitez\Simplicity\DBLayer
{
    private $pdo;
    private $userID;
    private $galleryTable;
    private $photoTable;
    private $deleteStatus = false;
    private $extendedStatus = '';

    public function set_params($app = [])
    {
        $user = $this->config_object->getUser();

        $this->userID = $user['username'] ?? $user['user_id'] ?? null;
        $this->galleryTable = $app['gallery-table'] ?? "users__gallery_groups";
        $this->photoTable = $app['photo-table'] ?? "users__gallery_items";
    }
    public function listGalleries($app = false)
    {
        $whereClause = '';
        // print_r($this->config['user']);
        // exit;
        //$params = [$this->userID];
        $this->set_params($app);
        $statuses = $app['statuses'] ?? false;
        $username = $this->userID;

        if ($statuses !== false) {
            // If statuses are specified, create a WHERE clause to filter galleries
            $statusPlaceholders = array_fill(0, count($statuses), '?');
            $whereClause = ' AND status IN (' . implode(',', $statusPlaceholders) . ')';
            $params = array_merge($params, $statuses);
        }
        $query = 'SELECT * FROM ' . $this->galleryTable . ' WHERE g_user = ?' . $whereClause;

        $params = [$username];

        $galleries = [];

        try {
            // List galleries based on status (or show all if $statuses is false)
            $query = 'SELECT * FROM ' . $this->galleryTable . ' WHERE g_user = ?' . $whereClause;
            //print $query;
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            $galleries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }

        return $galleries;
    }

    public function updatePhoto($photoID, $title, $description, $status)
    {
        $this->set_params([]);

        try {
            // Verify that the gallery belongs to the user
            $stmtVerifyOwnership = $this->connection->prepare('SELECT COUNT(*) FROM ' . $this->photoTable . ' WHERE p_id = ? AND g_user = ?');
            $stmtVerifyOwnership->execute([$photoID, $this->userID]);
            $isPhotoOwner = $stmtVerifyOwnership->fetchColumn();
            //print "-1- ";
            if ($isPhotoOwner) {
                //print "hi";exit;
                // Update the gallery's title and description
                $stmtUpdateGallery = $this->connection->prepare('UPDATE ' . $this->photoTable . ' SET title = ?, description = ? WHERE p_id = ? AND g_user = ?');
                $stmtUpdateGallery->execute([$title, $description, $photoID, $this->userID]);
                //print "-2- ";
                $this->extendedStatus = 'Photo updated successfully.';
                return true;
            } else {
                //print "-3- ";exit;
                $this->extendedStatus = 'You do not have permission to update this Photo.';
                return false;
                //print "nonono";exit;
            }
        } catch (PDOException $e) {
            $this->extendedStatus = 'Database error: ' . $e->getMessage();
            print $this->extendedStatus;
            exit;
            return false;
        }
        return false;
        //return $this->extendedStatus;
    }

    public function updateGallery($galleryID, $title, $description)
    {
        $this->set_params([]);

        try {
            // Verify that the gallery belongs to the user
            $stmtVerifyOwnership = $this->connection->prepare('SELECT COUNT(*) FROM ' . $this->galleryTable . ' WHERE g_id = ? AND g_user = ?');
            $stmtVerifyOwnership->execute([$galleryID, $this->userID]);
            $isGalleryOwner = $stmtVerifyOwnership->fetchColumn();
            //print "-1- ";
            if ($isGalleryOwner) {
                // Update the gallery's title and description
                $stmtUpdateGallery = $this->connection->prepare('UPDATE ' . $this->galleryTable . ' SET title = ?, description = ? WHERE g_id = ? AND g_user = ?');
                $stmtUpdateGallery->execute([$title, $description, $galleryID, $this->userID]);
                //print "-2- ";
                $this->extendedStatus = 'Gallery updated successfully.';
                return true;
            } else {
                //print "-3- ";
                $this->extendedStatus = 'You do not have permission to update this gallery.';
                return false;
                //print "nonono";exit;
            }
        } catch (PDOException $e) {
            $this->extendedStatus = 'Database error: ' . $e->getMessage();
            //print $this->extendedStatus;exit;
            return false;
        }
        return false;
        //return $this->extendedStatus;
    }

    public function insertGallery($title, $description, $siteID = null)
    {
        $this->set_params([]);
        try {
            // Insert a new gallery
            $slug = $this->generateUniqueSlug($title);
            $userID = $this->userID;
            // print $this->galleryTable;
            // exit;
            $stmtInsertGallery = $this->connection->prepare('INSERT INTO ' . $this->galleryTable . ' (slug, g_user, title, description, status, site_id) VALUES (?, ?, ?, ?, "active", ?)');
            $stmtInsertGallery->execute([$slug, $userID, $title, $description, $siteID]);

            // Get the inserted gallery ID
            $galleryID = $this->connection->lastInsertId();
            
            $this->extendedStatus = 'Gallery inserted successfully.';
            //print "hi";exit;
            return $galleryID; // Return the new gallery ID instead of just true
        } catch (PDOException $e) {
            $this->extendedStatus = 'Database error: ' . $e->getMessage();
            // print_r($this->extendedStatus); 
            // print_r($dbh->errorInfo());
            // exit;
            return false;
        }

        return false;
    }
    public function insertPhoto($galleryID, $title, $description, $uploadedFilePath)
    {
        $this->set_params([]);
        try {
            // Insert a new gallery
            $slug = $this->generateUniqueSlug($title);
            $userID = $this->userID;
            $status = "active";
            // print $this->galleryTable;
            // exit;
            $stmtInsertGallery = $this->connection->prepare('INSERT INTO ' . $this->photoTable . ' (slug, g_id, g_user, title, description, fname, status) VALUES (?, ?, ?, ?, ?, ?, ?)' . "\n");
            $stmtInsertGallery->execute([$slug, $galleryID, $userID, $title, $description, $uploadedFilePath, $status]);
            $this->extendedStatus = 'Photo inserted successfully.';
            //print "hi";exit;
            return true;
        } catch (PDOException $e) {
            $this->extendedStatus = 'Database error: ' . $e->getMessage();
            //print_r($this->extendedStatus); 
            // print_r($dbh->errorInfo());
            // exit;
            return false;
        }
        return false;
    }

    public function getPhotosByGalleryID($galleryID)
    {
        $this->set_params([]);
        try {
            // Prepare SQL statement
            $stmt = $this->connection->prepare("SELECT * FROM " . $this->photoTable . " WHERE g_id = :gallery_id");

            // Bind parameters
            $stmt->bindParam(':gallery_id', $galleryID, PDO::PARAM_INT);

            // Execute the query
            $stmt->execute();

            // Fetch all rows as associative arrays
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $photos;
        } catch (PDOException $e) {
            // Handle database error
            // For simplicity, we'll just echo the error message
            $this->extendedStatus = 'Database error: ' . $e->getMessage();
            return []; // Return an empty array on error
        }
    }


    public function getDeleteStatus()
    {
        return $this->deleteStatus;
    }

    public function getExtendedDeleteStatus()
    {
        return $this->extendedStatus;
    }

    public function deleteGallery($galleryID)
    {
        $this->set_params([]);
        $userID = $this->userID;
        try {
            $this->connection->beginTransaction();

            // Verify that the gallery belongs to the user
            $stmtVerifyOwnership = $this->connection->prepare('SELECT COUNT(*) FROM ' . $this->galleryTable . ' WHERE g_id = ? AND g_user = ?');
            $stmtVerifyOwnership->execute([$galleryID, $userID]);
            $isGalleryOwner = $stmtVerifyOwnership->fetchColumn();

            //$stmt->debugDumpParams();
            //print "$isGalleryOwner - $galleryID $userID " . $this->galleryTable;exit;

            if ($isGalleryOwner) {
                // Get the IDs of photos in the gallery
                //print "Gallery owner";exit;
                $stmtGetPhotoIDs = $this->connection->prepare('SELECT p_id FROM ' . $this->photoTable . ' WHERE g_id = ?');
                $stmtGetPhotoIDs->execute([$galleryID]);
                $photoIDs = $stmtGetPhotoIDs->fetchAll(PDO::FETCH_COLUMN, 0);

                // Mark the gallery as "deleted"
                $stmtUpdateGallery = $this->connection->prepare('UPDATE ' . $this->galleryTable . ' SET status = "deleted" WHERE g_id = ? AND g_user = ?');
                $stmtUpdateGallery->execute([$galleryID, $this->userID]);

                // Mark associated photos as "deleted"
                if (!empty($photoIDs)) {
                    $stmtUpdatePhotos = $this->connection->prepare('UPDATE ' . $this->photoTable . ' SET status = "deleted" WHERE p_id IN (' . implode(',', $photoIDs) . ')');
                    $stmtUpdatePhotos->execute();
                }

                $this->connection->commit();

                $this->deleteStatus = true;
                $this->extendedStatus = 'Gallery and associated photos marked as deleted successfully.';
            } else {
                $this->deleteStatus = false;
                $this->extendedStatus = 'You do not have permission to delete this gallery.';
            }
        } catch (PDOException $e) {
            $this->connection->rollBack();
            $this->deleteStatus = false;
            $this->extendedStatus = 'Database error: ' . $e->getMessage();
        }

        return $this->deleteStatus;
    }


    public function getUserGalleries($status = 'active')
    {
        $galleries = [];

        try {
            if ($status) {
                $stmt = $this->pdo->prepare('SELECT * FROM ' . $this->galleryTable . ' WHERE g_user = ? AND status = ?');
                $stmt->execute([$this->userID, $status]);
            } else {
                $stmt = $this->pdo->prepare('SELECT * FROM ' . $this->galleryTable . ' WHERE g_user = ? ');
                $stmt->execute([$this->userID]);
            }
            $galleries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
            die("Database error: " . $e->getMessage());
        }

        return $galleries;
    }
    public function getGalleryByID($galleryID)
    {
        $this->set_params([]);
        try {
            $query = "SELECT * FROM " . $this->galleryTable . " WHERE g_id = :galleryID AND g_user = :userID";
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(':galleryID', $galleryID, PDO::PARAM_INT);
            $stmt->bindParam(':userID', $this->userID, PDO::PARAM_INT);
            $stmt->execute();

            $gallery = $stmt->fetch(PDO::FETCH_ASSOC);

            return $gallery;
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    }

    public function getPhotoByID($photoID)
    {
        $this->set_params([]);
        try {
            $query = "SELECT * FROM " . $this->photoTable . " WHERE p_id = :photoID";
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(':photoID', $photoID, PDO::PARAM_INT);
            //$stmt->bindParam(':userID', $this->userID, PDO::PARAM_INT);
            $stmt->execute();

            $photo = $stmt->fetch(PDO::FETCH_ASSOC);
            //print_r($photo);
            return $photo;
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    }
    public function getGalleryPhotosByID($galleryID)
    {
        $this->set_params([]);
        try {
            $query = "SELECT * FROM " . $this->photoTable . " WHERE g_id = :galleryID";
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(':galleryID', $galleryID, PDO::PARAM_INT);
            //$stmt->bindParam(':userID', $this->userID, PDO::PARAM_INT);
            $stmt->execute();

            $gallery = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $gallery;
        } catch (PDOException $e) {
            die("Database error: " . $e->getMessage());
        }
    }
    private function generateUniqueSlug($title)
    {
        // Remove special characters and spaces from the title
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

        // Append a unique identifier to the slug (e.g., current timestamp)
        $unique_identifier = time(); // You can use other methods to generate unique identifiers
        $slug .= '-' . $unique_identifier;

        return $slug;
    }
}
