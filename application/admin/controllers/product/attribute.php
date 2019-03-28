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

    public function index() {
        $data = [];
        /** @var Attribute $Attribute */
        $Attribute = $this->load('Attribute', $this->registry);
        $data['Attributes'] = $Attribute->getAttributes();
        $data['Languages'] = $this->Language->getLanguages();
        $data['DefaultLanguageID'] = $this->Language->getDefaultLanguageID();
        $this->Response->setOutPut($this->render('product/attribute/index', $data));
    }

    public function delete() {
        if(!empty($this->Request->post['attributes_id'])) {
            $json = [];
            /** @var Attribute $Attribute */
            $Attribute = $this->load('Attribute', $this->registry);
            $error = false;
            $this->Database->db->beginTransaction();
            foreach ($this->Request->post['attributes_id'] as $attribute_id) {
                $attribute = $Attribute->getAttribute((int) $attribute_id);
                if($attribute && (int) $attribute_id) {
                    $Attribute->deleteAttribute((int) $attribute_id);
                }else {
                    $error = true;
                }
            }
            if($error) {
                $this->Database->db->rollBack();
                $json['status'] = 0;
                $json['messages'] = [$this->Language->get('error_done')];
            }else {
                $this->Database->db->commit();
                $json['status'] = 1;
                $json['messages'] = [$this->Language->get('success_message')];
                $data['Attributes'] = $Attribute->getAttributes(array(
                    'language_id'   => $this->Language->getLanguageID(),
                ));
                $json['data'] = $this->render('product/attribute/attribute_table', $data);
            }
            $this->Response->setOutPut(json_encode($json));
        }else {
            return new Action('error/notFound', 'web');
        }
    }

    public function edit() {
        $data = [];
        $messages = [];
        $error = false;
        if(isset($this->Request->get[0])) {
            $attribute_id = (int) $this->Request->get[0];
            /** @var Attributegroup $AttributeGroup */
            $AttributeGroup = $this->load('AttributeGroup', $this->registry);
            /** @var Attribute $Attribute */
            $Attribute = $this->load('Attribute', $this->registry);

            $attributeTotal = $Attribute->getAttribute($attribute_id, 'all');
            if($attribute_id && $attributeTotal) {
                $attributeInfo = [];
                foreach ($attributeTotal as $aattribute) {
                    $attributeInfo['attribute_names'][$aattribute['language_id']] = $aattribute['name'];
                }
                $attributeInfo['sort_order'] = $attributeTotal[0]['sort_order'];
                $attributeInfo['attribute_group_id'] = $attributeTotal[0]['attribute_group_id'];
                $attributeInfo['attribute_id'] = $attributeTotal[0]['attribute_id'];
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
                    if(!empty($this->Request->post['attribute-group-id']) && $AttributeGroup->getAttributeGroup((int) $this->Request->post['attribute-group-id'])) {
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
                        $add = [];
                        $delete = [];
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
                        if($attributeInfo['sort_order'] == $data['sort_order']) {
                            unset($data['sort_order']);
                        }
                        if($attributeInfo['attribute_group_id'] == $data['attribute_group_id']) {
                            unset($data['attribute_group_id']);
                        }

                        foreach ($this->Language->getLanguages() as $l) {
                            if(isset($attributeInfo['attribute_names'][$l['language_id']])
                                && isset($data['attribute_names'][$l['language_id']])
                                && $data['attribute_names'][$l['language_id']] ==
                                $attributeInfo['attribute_names'][$l['language_id']]) {
                                unset($data['attribute_names'][$l['language_id']]);
                            }else if (isset($attributeInfo['attribute_names'][$l['language_id']])
                                && !isset($data['attribute_names'][$l['language_id']])) {
                                $delete['attribute_names'][$l['language_id']] = $attributeInfo['attribute_names'][$l['language_id']];
                            }else if(!isset($attributeInfo['attribute_names'][$l['language_id']])
                                && isset($data['attribute_names'][$l['language_id']])) {
                                $add['attribute_names'][$l['language_id']] = $data['attribute_names'][$l['language_id']];
                                unset($data['attribute_names'][$l['language_id']]);
                            }
                        }
                        if(count($data['attribute_names']) == 0) {
                            unset($data['attribute_names']);
                        }
                        if(count($data) > 0) {
                            $Attribute->editAttribute($attribute_id, $data);
                        }
                        if(count($add) > 0) {
                            $Attribute->insertAttribute($add, $attribute_id);
                        }
                        if(count($delete) > 0) {
                            $Attribute->deleteAttribute($attribute_id, $delete);
                        }

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
                    $data['DefaultLanguageID'] = $this->Language->getLanguageID();
                    $data['AttributeGroups'] = $AttributeGroup->getAttributeGroups();
                    $data['Attribute'] = $attributeInfo;
                    $this->Response->setOutPut($this->render('product/attribute/edit', $data));
                }
                return;
            }
        }
        return new Action('error/notFound', 'web');
    }

}