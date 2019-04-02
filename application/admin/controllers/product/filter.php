<?php

namespace App\Admin\Controller;

use App\Lib\Database;
use App\lib\Request;
use App\lib\Response;
use App\model\Filter;
use App\system\Controller;

/**
 * @property Response Response
 * @property Request Request
 * @property Database Database
 * @property Filter Filter
 */
class ControllerProductFilter extends Controller {

    public function add() {
        $data = [];
        $messages = [];
        $error = false;

        if(isset($this->Request->post['filter-post'])) {
            $languages = $this->Language->getLanguages();
            $defaultLanguageID = $this->Language->getDefaultLanguageID();
            foreach ($languages as $language) {
                if(!empty($this->Request->post['filter-name-' . $language['language_id']])) {
                    $data['filter_names'][$language['language_id']] = $this->Request->post['filter-name-' . $language['language_id']];
                }
            }
            if(empty($this->Request->post['filter-name-' . $defaultLanguageID])) {
                $error = true;
                $messages[] = $this->Language->get('error_filter_name_empty');
            }
            $filters = [];
            if(isset($this->Request->post['filter-items']) && count($this->Request->post['filter-items']) > 0) {
                foreach ($this->Request->post['filter-items'] as $filterItem) {
                    $filter = [];
                    $filter['sort_order'] = $filterItem['sort_order'];
                    $filter['names'] = [];
                    foreach ($this->Language->getLanguages() as $language) {
                        if(!empty($filterItem['name-' . $language['language_id']])) {
                            $filter['names'][$language['language_id']] = $filterItem['name-' . $language['language_id']];
                        }
                    }
                    $filters[] = $filter;
                }
            }
            foreach ($filters as $filter) {
                if(!empty($filter['name'][$defaultLanguageID])) {
                    $error = true;
                    $messages[] = $this->Language->get('error_filter_item_name_empty');
                }
            }
            $data['filters'] = $filters;
            if(!empty($this->Request->post['filter-group-sort-order'])) {
                $data['sort_order'] = (int) $this->Request->post['filter-group-sort-order'];
            }else {
                $data['sort_order'] = 0;
            }
            $json = [];
            if(!$error) {
                /** @var Filter $Filter */
                $Filter = $this->load("Filter", $this->registry);
                if($data['sort_order'] == 0) {
                    $rows = $Filter->getFilterGroups(array(
                        'sort_order'    => 'sort_order',
                        'order'         => 'DESC',
                        'language_id'   => $this->Language->getLanguageID(),
                        'start'         => 0,
                        'limit'         => 1
                    ));
                    $oldSortOrder = count($rows) > 0 ? $rows[0]['sort_order'] : 0;
                    $data['sort_order'] = $oldSortOrder + 1;
                }
                $filter_group_id = $Filter->insertFilterGroup($data);
                $Filter->insertFilterItems($filter_group_id, $data['filters']);
                $json['status'] = 1;
                $json['messages'] = [$this->Language->get('success_message')];
                $json['redirect'] = ADMIN_URL . 'product/filter/index?token=' . $_SESSION['token'];
            }
            if($error) {
                $json['status'] = 0;
                $json['messages'] = $messages;
            }
            $this->Response->setOutPut(json_encode($json));
        }else {

            $data['Languages'] = $this->Language->getLanguages();
            $data['DefaultLanguageID'] = $this->Language->getDefaultLanguageID();

            $this->Response->setOutPut($this->render('product/filter/add', $data));
        }
    }

    public function index() {
        $data = [];
        /** @var Filter $Filter */
        $Filter = $this->load('Filter', $this->registry);
        $data['Languages'] = $this->Language->getLanguages();
        $data['DefaultLanguageID'] = $this->Language->getDefaultLanguageID();
        $data['Filters'] = $Filter->getFilterGroups(array(
            'language_id'   => $this->Language->getLanguageID(),
        ));
        $this->Response->setOutPut($this->render('product/filter/index', $data));
    }

    public function delete() {
        if(!empty($this->Request->post['filtergroups_id'])) {
            $json = [];
            /** @var Filter $Filter */
            $Filter = $this->load('Filter', $this->registry);
            $error = false;
            $this->Database->db->beginTransaction();
            foreach ($this->Request->post['filtergroups_id'] as $filter_group_id) {
                $fitlerGroup = $Filter->getFilterGroup((int) $filter_group_id);
                if($fitlerGroup && (int) $filter_group_id) {
                    $Filter->deleteFilterGroup((int) $filter_group_id);
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
                $data['Filters'] = $Filter->getFilterGroups(array(
                    'language_id'   => $this->Language->getLanguageID(),
                ));
                $json['data'] = $this->render('product/filter/filter_table', $data);
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
            $filter_group_id = (int) $this->Request->get[0];
            /** @var Filter $Filter */
            $Filter = $this->load('Filter', $this->registry);
            $filterGroupTotal = $Filter->getFilterGroup($filter_group_id, 'all');
            if($filter_group_id && $filterGroupTotal) {
                $filterGroupInfo = [];
                foreach ($filterGroupTotal as $fGroup) {
                    $filterGroupInfo['filter_names'][$fGroup['language_id']] = $fGroup['name'];
                }
                $filterGroupInfo['sort_order'] = $filterGroupTotal[0]['sort_order'];
                $filterGroupInfo['filter_group_id'] = $filterGroupTotal[0]['filter_group_id'];
                $filters = [];
                foreach ($Filter->getFilters() as $filter) {
                    $filters[$filter['filter_id']]['sort_order'] = $filter['sort_order'];
                    $filters[$filter['filter_id']]['names'][$filter['language_id']] = $filter['name'];
                }
                $filterGroupInfo['filters'] = $filters;
                if(isset($this->Request->post['filter-post'])) {
                    $languages = $this->Language->getLanguages();
                    $defaultLanguageID = $this->Language->getDefaultLanguageID();
                    foreach ($languages as $language) {
                        if(!empty($this->Request->post['filter-name-' . $language['language_id']])) {
                            $data['filter_names'][$language['language_id']] = $this->Request->post['filter-name-' . $language['language_id']];
                        }
                    }
                    if(empty($this->Request->post['filter-name-' . $defaultLanguageID])) {
                        $error = true;
                        $messages[] = $this->Language->get('error_filter_name_empty');
                    }
                    $filters = [];
                    if(isset($this->Request->post['filter-items']) && count($this->Request->post['filter-items']) > 0) {
                        foreach ($this->Request->post['filter-items'] as $filterItem) {
                            $filter = [];
                            $filter['sort_order'] = $filterItem['sort_order'];
                            $filter['names'] = [];
                            foreach ($this->Language->getLanguages() as $language) {
                                if(!empty($filterItem['name-' . $language['language_id']])) {
                                    $filter['names'][$language['language_id']] = $filterItem['name-' . $language['language_id']];
                                }
                            }
                            $filters[] = $filter;
                        }
                    }
                    foreach ($filters as $filter) {
                        if(!empty($filter['name'][$defaultLanguageID])) {
                            $error = true;
                            $messages[] = $this->Language->get('error_filter_item_name_empty');
                        }
                    }
                    $data['filters'] = $filters;
                    if(!empty($this->Request->post['filter-group-sort-order'])) {
                        $data['sort_order'] = (int) $this->Request->post['filter-group-sort-order'];
                    }else {
                        $data['sort_order'] = 0;
                    }
                    $json = [];

                    if(!$error) {
                        $add = [];
                        $delete = [];
                        if($data['sort_order'] == 0) {
                            $rows = $Filter->getFilters(array(
                                'sort_order'    => 'sort_order',
                                'order'         => 'DESC',
                                'language_id'   => $this->Language->getLanguageID(),
                                'start'         => 0,
                                'limit'         => 1
                            ));
                            $oldSortOrder = count($rows) > 0 ? $rows[0]['sort_order'] : 0;
                            $data['sort_order'] = $oldSortOrder + 1;
                        }
                        if($filterGroupInfo['sort_order'] == $data['sort_order']) {
                            unset($data['sort_order']);
                        }
                        foreach ($this->Language->getLanguages() as $l) {
                            if(isset($filterGroupInfo['filter_names'][$l['language_id']])
                                && isset($data['filter_names'][$l['language_id']])
                                && $data['filter_names'][$l['language_id']] ==
                                $filterGroupInfo['filter_names'][$l['language_id']]) {
                                unset($data['filter_names'][$l['language_id']]);
                            }else if (isset($filterGroupInfo['filter_names'][$l['language_id']])
                                && !isset($data['filter_names'][$l['language_id']])) {
                                $delete['filter_names'][$l['language_id']] = $filterGroupInfo['filter_names'][$l['language_id']];
                            }else if(!isset($filterGroupInfo['filter_names'][$l['language_id']])
                                && isset($data['filter_names'][$l['language_id']])) {
                                $add['filter_names'][$l['language_id']] = $data['filter_names'][$l['language_id']];
                                unset($data['filter_names'][$l['language_id']]);
                            }
                        }
                        if(count($data['filter_names']) == 0) {
                            unset($data['filter_names']);
                        }
                        if(count($data) > 0) {
                            $Filter->editFilterGroup($filter_group_id, $data);
                        }
                        if(count($add) > 0) {
                            $Filter->insertFilterGroup($add, $filter_group_id);
                        }
                        if(count($delete) > 0) {
                            $Filter->deleteFilterGroup($filter_group_id, $delete);
                        }

                        $json['status'] = 1;
                        $json['messages'] = [$this->Language->get('success_message')];
                        $json['redirect'] = ADMIN_URL . 'product/filter/index?token=' . $_SESSION['token'];
                    }
                    if($error) {
                        $json['status'] = 0;
                        $json['messages'] = $messages;
                    }
                    $this->Response->setOutPut(json_encode($json));
                }else {
                    $data['Languages'] = $this->Language->getLanguages();
                    $data['DefaultLanguageID'] = $this->Language->getLanguageID();
                    $data['Filter'] = $filterGroupInfo;
                    $this->Response->setOutPut($this->render('product/filter/edit', $data));
                }
                return;
            }
        }
        return new Action('error/notFound', 'web');
    }

}