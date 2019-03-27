<?php

namespace App\Admin\Controller;

use App\lib\Action;
use App\Lib\Database;
use App\lib\Request;
use App\lib\Response;
use App\model\Attributegroup;
use App\system\Controller;

/**
 * @property Response Response
 * @property Request Request
 * @property Database Database
 */
class ControllerProductAttributegroup extends Controller {

    public function index() {
        $data = [];
        /** @var Attributegroup $AttributeGroup */
        $AttributeGroup = $this->load('AttributeGroup', $this->registry);
        $data['Languages'] = $this->Language->getLanguages();
        $data['DefaultLanguageID'] = $this->Language->getDefaultLanguageID();
        $data['AttributeGroups'] = $AttributeGroup->getAttributeGroups(array(
            'language_id'   => $this->Language->getLanguageID(),
        ));
        $this->Response->setOutPut($this->render('product/attributegroup/index', $data));
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

    public function edit() {
        $data = [];
        $messages = [];
        $error = false;
        if(isset($this->Request->get[0])) {
            $attribute_group_id = (int) $this->Request->get[0];
            /** @var Attributegroup $AttributeGroup */
            $AttributeGroup = $this->load('AttributeGroup', $this->registry);
            $attributeGroupTotal = $AttributeGroup->getAttributeGroup($attribute_group_id, 'all');
            if($attribute_group_id && $attributeGroupTotal) {
                $attributeGroupInfo = [];
                foreach ($attributeGroupTotal as $aGroup) {
                    $attributeGroupInfo['attributegroup_names'][$aGroup['language_id']] = $aGroup['name'];
                }
                $attributeGroupInfo['sort_order'] = $attributeGroupTotal[0]['sort_order'];
                $attributeGroupInfo['attribute_group_id'] = $attributeGroupTotal[0]['attribute_group_id'];
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
                        $add = [];
                        $delete = [];
                        if($data['sort_order'] == 0) {
                            $rows = $AttributeGroup->getAttributeGroups(array(
                                'sort_order'    => 'sort_order',
                                'order'         => 'DESC',
                                'language_id'   => $this->Language->getLanguageID(),
                                'start'         => 0,
                                'limit'         => 1
                            ));
                            $oldSortOrder = count($rows) > 0 ? $rows[0]['sort_order'] : 0;
                            $data['sort_order'] = $oldSortOrder + 1;
                        }
                        if($attributeGroupInfo['sort_order'] == $data['sort_order']) {
                            unset($data['sort_order']);
                        }
                        foreach ($this->Language->getLanguages() as $l) {
                            if(isset($attributeGroupInfo['attributegroup_names'][$l['language_id']])
                                && isset($data['attributegroup_names'][$l['language_id']])
                                && $data['attributegroup_names'][$l['language_id']] ==
                                $attributeGroupInfo['attributegroup_names'][$l['language_id']]) {
                                unset($data['attributegroup_names'][$l['language_id']]);
                            }else if (isset($attributeGroupInfo['attributegroup_names'][$l['language_id']])
                                && !isset($data['attributegroup_names'][$l['language_id']])) {
                                $delete['attributegroup_names'][$l['language_id']] = $attributeGroupInfo['attributegroup_names'][$l['language_id']];
                            }else if(!isset($attributeGroupInfo['attributegroup_names'][$l['language_id']])
                                && isset($data['attributegroup_names'][$l['language_id']])) {
                                $add['attributegroup_names'][$l['language_id']] = $data['attributegroup_names'][$l['language_id']];
                                unset($data['attributegroup_names'][$l['language_id']]);
                            }
                        }
                        if(count($data['attributegroup_names']) == 0) {
                            unset($data['attributegroup_names']);
                        }
                        if(count($data) > 0) {
                            $AttributeGroup->editAttributeGroup($attribute_group_id, $data);
                        }
                        if(count($add) > 0) {
                            $AttributeGroup->insertAttributeGroup($add, $attribute_group_id);
                        }
                        if(count($delete) > 0) {
                            $AttributeGroup->deleteAttributeGroup($attribute_group_id, $delete);
                        }

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
                    $data['DefaultLanguageID'] = $this->Language->getLanguageID();
                    $data['AttributeGroup'] = $attributeGroupInfo;
                    $this->Response->setOutPut($this->render('product/attributegroup/edit', $data));
                }
                return;
            }
        }
        return new Action('error/notFound', 'web');
    }

    public function delete() {
        if(!empty($this->Request->post['attributegroups_id'])) {
            $json = [];
            /** @var Attributegroup $AttributeGroup */
            $AttributeGroup = $this->load('AttributeGroup', $this->registry);
            $error = false;
            $this->Database->db->beginTransaction();
            foreach ($this->Request->post['attributegroups_id'] as $attributegroup_id) {
                $attributeGroup = $AttributeGroup->getAttributeGroup((int) $attributegroup_id);
                if($attributeGroup && (int) $attributegroup_id) {
                    $AttributeGroup->deleteAttributeGroup((int) $attributegroup_id);
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
                $data['AttributeGroups'] = $AttributeGroup->getAttributeGroups(array(
                    'language_id'   => $this->Language->getLanguageID(),
                ));
                $json['data'] = $this->render('product/attributegroup/attributegroup_table', $data);
            }
            $this->Response->setOutPut(json_encode($json));
        }else {
            return new Action('error/notFound', 'web');
        }
    }
}