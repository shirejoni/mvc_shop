<?php


namespace App\Admin\Controller;

use App\Lib\Database;
use App\lib\Response;
use App\lib\Request;
use App\model\Attribute;
use App\model\Attributegroup;
use App\system\Controller;

/**
 * @property Response Response
 * @property Request Request
 * @property Database Database
 */
class ControllerProductAttribute extends Controller {

    public function add() {
        $data = [];
        $messages = [];
        $error = false;
        /** @var Attributegroup $Attributegroup */
        $Attributegroup = $this->load("Attributegroup", $this->registry);
        if(isset($this->Request->post['attribute-post'])) {
            $languages = $this->Language->getLanguages();
            $defaultLanguageID = $this->Language->getDefaultLanguageID();
            foreach ($languages as $language) {
                if(!empty($this->Request->post['attribute-name-' . $language['language_id']])) {
                    $data['attribute_names'][$language['language_id']] = $this->Request->post['attribute-name-' . $language['language_id']];
                }
            }
            if(empty($this->Request->post['attribute-name-' . $defaultLanguageID])) {
                $error = true;
                $messages[] = $this->Language->get('error_attribute_name_empty');
            }
            if(!empty($this->Request->post['attribute-group-id']) && $Attributegroup->getAttributeGroup((int) $this->Request->post['attribute-group-id'])) {
                $data['attribute_group_id'] = (int) $this->Request->post['attribute-group-id'];
            }else {
                $error = true;
                $messages[] = $this->Language->get('error_attribute_group_not_selected');
            }

            if(!empty($this->Request->post['attribute-sort-order'])) {
                $data['sort_order'] = (int) $this->Request->post['attribute-sort-order'];
            }else {
                $data['sort_order'] = 0;
            }
            $json = [];

            if(!$error) {

                /** @var Attribute $Attribute */
                $Attribute = $this->load('Attribute', $this->registry);
                if($data['sort_order'] == 0) {
                    $rows = $Attribute->getAttributes(array(
                        'sort_order'    => 'sort_order',
                        'order'         => 'DESC',
                        'language_id'   => $this->Language->getLanguageID(),
                        'start'         => 0,
                        'limit'         => 1
                    ));
                    $oldSortOrder = count($rows) > 0 ? $rows[0]['sort_order'] : 0;
                    $data['sort_order'] = $oldSortOrder + 1;
                }
                $Attribute->insertAttribute($data);
                $json['status'] = 1;
                $json['messages'] = [$this->Language->get('success_message')];
                $json['redirect'] = ADMIN_URL . 'product/attribute/index?token=' . $_SESSION['token'];
            }
            if($error) {
                $json['status'] = 0;
                $json['messages'] = $messages;
            }
            $this->Response->setOutPut(json_encode($json));
        }else {

            $data['Languages'] = $this->Language->getLanguages();
            $data['DefaultLanguageID'] = $this->Language->getDefaultLanguageID();
            $data['AttributeGroups'] = $Attributegroup->getAttributeGroups();
            $this->Response->setOutPut($this->render('product/attribute/add', $data));
        }


    }

}