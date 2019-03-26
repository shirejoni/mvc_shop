<?php

namespace App\Admin\Controller;

use App\lib\Request;
use App\lib\Response;
use App\model\Attributegroup;
use App\system\Controller;

/**
 * @property Response Response
 * @property Request Request
 */
class ControllerProductAttributegroup extends Controller {
    public function index() {

    }
    public function add() {
        $data = [];
        $messages = [];
        $error = false;

        if(isset($this->Request->post['attributegroup-post'])) {
            $languages = $this->Language->getLanguages();
            $defaultLanguageID = $this->Language->getDefaultLanguageID();
            foreach ($languages as $language) {
                if(!empty($this->Request->post['attributegroup-name-' . $language['language_id']])) {
                    $data['attributegroup_names'][$language['language_id']] = $this->Request->post['attributegroup-name-' . $language['language_id']];
                }
            }
            if(empty($this->Request->post['attributegroup-name-' . $defaultLanguageID])) {
               $error = true;
               $messages[] = $this->Language->get('error_attributegroup_name_empty');
            }

            if(!empty($this->Request->post['attributegroup-sort-order'])) {
                $data['sort_order'] = (int) $this->Request->post['attributegroup-sort-order'];
            }else {
                $data['sort_order'] = 0;
            }
            $json = [];

            if(!$error) {
                /** @var Attributegroup $Attributegroup */
                $Attributegroup = $this->load("Attributegroup", $this->registry);
                if($data['sort_order'] == 0) {
                    $rows = $Attributegroup->getAttributeGroups(array(
                        'sort_order'    => 'sort_order',
                        'order'         => 'DESC',
                        'language_id'   => $this->Language->getLanguageID(),
                        'start'         => 0,
                        'limit'         => 1
                    ));
                    $oldSortOrder = count($rows) > 0 ? $rows[0]['sort_order'] : 0;
                    $data['sort_order'] = $oldSortOrder + 1;
                }
                $Attributegroup->insertAttributeGroup($data);
                $json['status'] = 1;
                $json['messages'] = [$this->Language->get('success_message')];
                $json['redirect'] = ADMIN_URL . 'product/attributegroup/index?token=' . $_SESSION['token'];
            }
            if($error) {
                $json['status'] = 0;
                $json['messages'] = $messages;
            }
            $this->Response->setOutPut(json_encode($json));
        }else {

            $data['Languages'] = $this->Language->getLanguages();
            $data['DefaultLanguageID'] = $this->Language->getDefaultLanguageID();

            $this->Response->setOutPut($this->render('product/attributegroup/add', $data));
        }
    }

}