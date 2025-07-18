<?php

namespace Opensitez\Simplicity\Components;

use \PDO;

class FormModel extends \Opensitez\Simplicity\DBLayer
{
    protected $formsTable = "sites__forms";
    protected $routesTable = "sites__routes";
    protected $user;
    protected $userID;
    public function set_params($app = [])
    {
        //$this->connection = $this->connection;
        $this->user = $this->config_object->getUser();
        $this->userID = $this->user['username'];
        $this->formsTable = $app['form-table'] ?? $this->formsTable;
        $this->routesTable = $app['route-table'] ?? $this->routesTable;
    }

    public function getForms()
    {
        $query = "SELECT * FROM " . $this->formsTable;
        $result = $this->connection->query($query);

        if ($result) {
            return $result->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return [];
        }
    }
    function getFormById($formId)
    {
        // Prepare SQL statement to fetch form by ID
        $query = "SELECT * FROM " . $this->formsTable . " WHERE fid = :formId";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(':formId', $formId);

        // Execute the query
        if ($stmt->execute()) {
            // Fetch the form
            $site = $stmt->fetch(PDO::FETCH_ASSOC);
            return $site;
        } else {
            return false; // Error occurred
        }
    }
    public function addForm($formData)
    {
        $this->set_params();

        $data = [
            "form_name" => $formData['form_name'],
            "form_description" => $formData['form_description'] ?? "",
            "form_owner" => $this->userID,
            "site_id" => $formData['site_id'] ?? 1,
            "form_status" => $formData['form_status'] ?? "active",
            "form_definition" => $formData['form_definition'] ?? "{}",
        ];
        //$query = "INSERT INTO " . $this->formsTable . " (form_name, form_domain, form_owner, form_description, form_status, definition) VALUES (:form_name, :form_domain, :form,_owner, :form_description, :form_status, :definition)";
        $query = "INSERT INTO " . $this->formsTable .
            "        (form_name, site_id, form_owner, form_description, form_status, form_definition)"
            . " VALUES (:form_name, :site_id, :form_owner, :form_description, :form_status, :form_definition)";
        $stmt = $this->connection->prepare($query);
        $stmt->execute($data);
        return $this->connection->lastInsertId();
        //return $stmt->rowCount();
    }
    function updateForm($formData)
    {
        // Prepare SQL statement to update the form
        $data = [
            'formId' => $formData['form_id'],
            "formName" => $formData['form_name'] ?? "",
            "formDescription" => $formData['form_description'] ?? "",
            "formStatus" => $formData['form_status'] ?? "active",
            "formDefinition" => $formData['form_definition'] ?? "{}",
        ];
        $query = "UPDATE " . $this->formsTable . "
                  SET form_name = :formName, form_description = :formDescription, form_definition= :formDefinition, form_status = :formStatus
                  WHERE fid = :formId";
        $stmt = $this->connection->prepare($query);
        // $stmt->bindParam(':formName', $formName);
        // $stmt->bindParam(':formDescription', $formDescription);
        // $stmt->bindParam(':formId', $formId);

        // Execute the query
        if ($stmt->execute($data)) {
            return true; // Form updated successfully
        } else {
            return false; // Error occurred
        }
    }
    public function deleteForm($formID)
    {
        $query = "DELETE FROM " . $this->formsTable . " WHERE fid = :id";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(':id', $formID, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }
}
