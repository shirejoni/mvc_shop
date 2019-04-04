<?php

namespace App\Admin\Controller;

use App\lib\Action;
use App\Lib\Database;
use App\lib\Request;
use App\lib\Response;
use App\lib\Validate;
use App\model\Option;
use App\system\Controller;

/**
 * @property Response Response
 * @property Request Request
 * @property Database Database
 * @property Option Option
 */
class ControllerProductOption extends Controller {

    public function index() {
        $data = [];
        /** @var Option $Option */
        $Option = $this->load('Option', $this->registry);
        $data['Languages'] = $this->Language->getLanguages();
        $data['DefaultLanguageID'] = $this->Language->getDefaultLanguageID();
        $data['OptionGroups'] = $Option->getOptionGroups(array(
            'language_id'   => $this->Language->getLanguageID(),
        ));
        $this->Response->setOutPut($this->render('product/option/index', $data));
    }

    public function add() {
        $data = [];
        $messages = [];
        $error = false;

        if(isset($this->Request->post['option-post'])) {
            $languages = $this->Language->getLanguages();
            $defaultLanguageID = $this->Language->getDefaultLanguageID();
            foreach ($languages as $language) {
                if(!empty($this->Request->post['option-group-name-' . $language['language_id']])) {
                    $data['optiongroup_names'][$language['language_id']] = $this->Request->post['option-group-name-' . $language['language_id']];
                }
            }
            if(empty($this->Request->post['option-group-name-' . $defaultLanguageID])) {
                $error = true;
                $messages[] = $this->Language->get('error_option_group_name_empty');
            }
            $options = [];
            if(isset($this->Request->post['option-items']) && count($this->Request->post['option-items']) > 0) {
                foreach ($this->Request->post['option-items'] as $optionItem) {
                    $option = [];
                    $option['sort_order'] = $optionItem['sort_order'];
                    $option['image'] = Validate::urlValid($optionItem['image']) ? $optionItem['image'] : '';
                    $option['names'] = [];
                    foreach ($this->Language->getLanguages() as $language) {
                        if(!empty($optionItem['name-' . $language['language_id']])) {
                            $option['names'][$language['language_id']] = $optionItem['name-' . $language['language_id']];
                        }
                    }
                    $options[] = $option;
                }
            }
            foreach ($options as $option) {
                if(!empty($option['name'][$defaultLanguageID])) {
                    $error = true;
                    $messages[] = $this->Language->get('error_option_item_name_empty');
                }
            }
            $data['options'] = $options;
            if(!empty($this->Request->post['option-group-sort-order'])) {
                $data['sort_order'] = (int) $this->Request->post['filter-group-sort-order'];
            }else {
                $data['sort_order'] = 0;
            }
            if(!empty($this->Request->post['option-type']) && in_array($this->Request->post['option-type'], $this->Config->get('option_type'))) {
                $data['type'] =  $this->Request->post['option-type'];
            }else {
                $error = true;
                $messages[] = $this->Language->get('error_option_type_select_empty');
            }
            $json = [];
            if(!$error) {
                /** @var Option $Option */
                $Option = $this->load("Option", $this->registry);
                if($data['sort_order'] == 0) {
                    $rows = $Option->getOptionGroups(array(
                        'sort_order'    => 'sort_order',
                        'order'         => 'DESC',
                        'language_id'   => $this->Language->getLanguageID(),
                        'start'         => 0,
                        'limit'         => 1
                    ));
                    $oldSortOrder = count($rows) > 0 ? $rows[0]['sort_order'] : 0;
                    $data['sort_order'] = $oldSortOrder + 1;
                }
                $filter_group_id = $Option->insertOptionGroup($data);
                $Option->insertOptionItems($filter_group_id, $data['options']);
                $json['status'] = 1;
                $json['messages'] = [$this->Language->get('success_message')];
                $json['redirect'] = ADMIN_URL . 'product/option/index?token=' . $_SESSION['token'];
            }
            if($error) {
                $json['status'] = 0;
                $json['messages'] = $messages;
            }
            $this->Response->setOutPut(json_encode($json));
        }else {

            $data['Languages'] = $this->Language->getLanguages();
            $data['DefaultLanguageID'] = $this->Language->getDefaultLanguageID();
            $data['OptionTypes'] = $this->Config->get('option_type');
            $this->Response->setOutPut($this->render('product/option/add', $data));
        }
    }

    public function delete() {
        if(!empty($this->Request->post['optiongroups_id'])) {
            $json = [];
            /** @var Option $Option */
            $Option = $this->load('Option', $this->registry);
            $error = false;
            $this->Database->db->beginTransaction();
            foreach ($this->Request->post['optiongroups_id'] as $optiongroup_id) {
                $optionGroup = $Option->getOptionGroup((int) $optiongroup_id);
                if($optionGroup && (int) $optiongroup_id) {
                    $Option->deleteOptionGroup((int) $optiongroup_id);
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
                $data['OptionGroups'] = $Option->getOptionGroups(array(
                    'language_id'   => $this->Language->getLanguageID(),
                ));
                $json['data'] = $this->render('product/option/option_table', $data);
            }
            $this->Response->setOutPut(json_encode($json));
        }else {
            return new Action('error/notFound', 'web');
        }
    }

}