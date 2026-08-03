<?php

#[\AllowDynamicProperties]
Class ArtifyAjaxCtrl {
    private function sanitizeString($value) {
        if ($value === null) {
            return null;
        }
        $value = strip_tags((string) $value);
        return str_replace(array("'", '"'), array('&#39;', '&#34;'), $value);
    }

    private function sanitizeArray($data) {
        if (!is_array($data)) {
            return $this->sanitizeString($data);
        }
        $clean = array();
        foreach ($data as $key => $val) {
            $clean[$key] = is_array($val) ? $this->sanitizeArray($val) : $this->sanitizeString($val);
        }
        return $clean;
    }

    public function handleRequest() {
        $instanceKey = null;

        if (isset($_POST["artify_instance"])) {
            $instanceKey = $this->sanitizeString($_POST["artify_instance"]);
        } elseif (isset($_GET["artify_instance"])) {
            $instanceKey = $this->sanitizeString($_GET["artify_instance"]);
        }
        
        if(!isset($_SESSION["artify_sess"][$instanceKey])){
            die("La sesión ha caducado. Actualice su página para continuar.");
        }

        $artify = @unserialize(
            $_SESSION["artify_sess"][$instanceKey],
            ['allowed_classes' => true]
        );

        if (!is_object($artify)) {
            exit("Ocurrió un error. Por favor, inténtelo de nuevo más tarde.");
        }

        $action = isset($_POST["artify_data"]["action"]) ? $this->sanitizeString($_POST["artify_data"]["action"]) : null;
        $data = isset($_POST["artify_data"]) ? $this->sanitizeArray($_POST["artify_data"]) : [];
        $post = $_POST;
        if (isset($_FILES)) {
            $post = array_merge($_FILES, $post);
        }
        $data["post"] = $post;
        switch (strtoupper($action)) {
            case "VIEW":
                echo $artify->render("VIEWFORM", $data);
                break;
            case "SORT":
                $artify->setBackOperation();
                $data["action"] = "asc";
                echo $artify->render("CRUD", $data);
                break;
            case "ASC":
                $artify->setBackOperation();
                echo $artify->render("CRUD", $data);
                break;
            case "DESC":
                $artify->setBackOperation();
                echo $artify->render("CRUD", $data);
                break;
            case "ADD":
                echo $artify->render("INSERTFORM", $data);
                break;
            case "INSERT":
                $artify->render("INSERT", $post);
                break;
            case "INSERT_CLOSE":
                $artify->setBackOperation();
                $artify->render("INSERT", $post);
                break;
            case "INSERT_BACK":
                $artify->setBackOperation();
                $artify->render("INSERT", $post);
                $artify->setBackOperation();
                echo $artify->render("CRUD", $data);
                break;
            case "BACK":
                $artify->setBackOperation();
                echo $artify->render("CRUD", $data);
                break;
            case "EDIT":
                $artify->setInlineEdit(false);
                echo $artify->render("EDITFORM", $data);
                break;
            case "INLINE_EDIT":
                $artify->setBackOperation();
                $artify->setInlineEdit(true);
                echo $artify->render("EDITFORM", $data);
                break;
            case "ONEPAGEEDIT":
                $artify->setBackOperation();
                $artify->setInlineEdit(false);
                echo $artify->render("ONEPAGE", $data);
                break;
            case "ONEPAGEVIEW":
                $artify->setBackOperation();
                echo $artify->render("ONEPAGE", $data);
                break;
            case "QUICK_VIEW":
                echo $artify->render("QUICKVIEW", $data);
                break;
            case "RELATED_TABLE":
                echo $artify->render("RELATED_TABLE", $data);
                break;
            case "INLINE_BACK":
                $artify->render("UPDATE", $post);
                echo $artify->render("CRUD", $data);
                break;
            case "UPDATE":
                $artify->render("UPDATE", $post);
                break;
            case "UPDATE_BACK":
                $artify->setBackOperation();
                $artify->render("UPDATE", $post);
                $artify->setBackOperation();
                echo $artify->render("CRUD", $data);
                break;
            case "UPDATE_CLOSE":
                $artify->setBackOperation();
                $artify->render("UPDATE", $post);
                break;
            case "DELETE":
                $artify->render("DELETE", $data);
                $artify->setBackOperation();
                echo $artify->render("CRUD", $data);
                break;
            case "DELETE_SELECTED":
                $artify->render("DELETE_SELECTED", $data);
                $artify->setBackOperation();
                echo $artify->render("CRUD", $data);
                break;
            case "PAGINATION":
                if($data["rendertype"] == "CRUD"){
                    $artify->setBackOperation();
                    $artify->currentPage($data["page"]);
                    echo $artify->render("CRUD", $data);
                } else {
                    $artify->currentPage($data["page"]);
                    echo $artify->render("SQL", $data);
                }
                break;
            case "RECORDS_PER_PAGE":
                if($data["rendertype"] == "CRUD"){
                    $artify->currentPage(1);
                    $artify->recordsPerPage($data["records"]);
                    echo $artify->render("CRUD", $data);
                } else {
                    $artify->currentPage(1);
                    $artify->recordsPerPage($data["records"]);
                    echo $artify->render("SQL", $data);
                }
                break;
            case "SEARCH":
                if($data["rendertype"] == "CRUD"){
                    $artify->currentPage(1);
                    echo $artify->render("CRUD", $data);
                } else {
                    $artify->currentPage(1);
                    echo $artify->render("SQL", $data);
                }
                break;
            case "AUTOSUGGEST":
                if (isset($_GET["callback"])) {
                    $data["callback"] = $this->sanitizeString($_GET["callback"]);
                }
                echo $artify->render("AUTOSUGGEST", $data);
                break;
            case "EXPORTTABLE":
                echo $artify->render("EXPORTTABLE", $data);
                break;
            case "EXPORTFORM":
                $artify->render("EXPORTFORM", $data);
                break;
            case "SWITCH":
                $artify->setBackOperation();
                $artify->render("SWITCH", $data);
                echo $artify->render("CRUD", $data);
                break;
            case "BTNSWITCH":
                $artify->setBackOperation();
                $artify->render("BTNSWITCH", $data);
                echo $artify->render("CRUD", $data);
                break;
            case "LOADDEPENDENT":
                echo $artify->render("LOADDEPENDENT", $data);
                break;
            case "EMAIL" : echo $artify->render("EMAIL", $post);
                break;
            case "SELECT":
                $artify->setBackOperation();
                echo $artify->render("CRUD", $data);
                break;
            case "SELECTFORM":
                echo $artify->render("SELECT", $post);
                break;
            case "FILTER":
                $artify->setBackOperation();
                $artify->currentPage(1);
                echo $artify->render("CRUD", $data);
                break;
            case "REFRESH":
                if($data["rendertype"] == "CRUD"){
                    echo $artify->render("CRUD", $data);
                } else {
                    echo $artify->render("SQL", $data);
                }
                break;
            case "RELOAD":
                $artify->setBackOperation();
                echo $artify->render("CRUD", $data);
                break;
            case "SAVE_CRUD_TABLE_DATA":
                $artify->setBackOperation();
                $artify->render("SAVE_CRUD_DATA", $data);
                echo $artify->render("CRUD", $data);
                break;
            case "RENDER_ADV_SEARCH":
                $artify->currentPage(1);
                echo $artify->render("CRUD", $data);
                break;
            case "DATE_RANGE_REPORT":
                $artify->setBackOperation();
                $artify->currentPage(1);
                echo $artify->render("CRUD", $data);
                break;
            case "CLONE":
                echo $artify->render("CLONEFORM", $data);
                break;
            case "CELL_UPDATE":
                $updateData[$data["column"]] = $data["content"];
                 if (isset($data["id"]))
                    $artify->setPrimarykeyValue($data["id"]);
                $artify->render("UPDATE", $updateData);
                break;
            case "AJAX_ACTION":
                echo $artify->render("AJAX_ACTION", $data);
                break;   
            case "PRINTPDF":
                echo $artify->render("PRINT_PDF", $data);
                break;   
            default:
                break;
        }
    }
}