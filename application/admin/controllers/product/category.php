<?php

namespace App\Admin\Controller;

use App\Lib\Database;
use App\lib\Request;
use App\lib\Response;
use App\model\Category;
use App\model\Filter;
use App\system\Controller;

/**
 * @property Response Response
 * @property Request Request
 * @property Database Database
 */
class ControllerProductCategory extends Controller {

    public function index() {
        $data = [];
        /** @var Category $Category */
        $Category = $this->load('Category', $this->registry);
        $data['Languages'] = $this->Language->getLanguages();
        $data['DefaultLanguageID'] = $this->Language->getDefaultLanguageID();
        $data['Categories'] = $Category->getCategoriesComp(array(
            'language_id'   => $this->Language->getLanguageID(),
            'order'         => 'DESC'
        ));
        foreach ($data['Categories'] as $index =>  $category) {
            $data['Categories'][$index]['full_name'] = implode(' > ', explode(',', $category['full_name']));
        }
        $this->Response->setOutPut($this->render('product/category/index', $data));
    }

    public function add() {
        $data = [];
        $messages = [];
        $error = false;

        if(isset($this->Request->post['category-post'])) {
            /** @var Category $Category */
            $Category = $this->load("Category", $this->registry);
            $languages = $this->Language->getLanguages();
            $defaultLanguageID = $this->Language->getDefaultLanguageID();
            foreach ($languages as $language) {
                if(!empty($this->Request->post['category-name-' . $language['language_id']])) {
                    $data['category_names'][$language['language_id']] = $this->Request->post['category-name-' . $language['language_id']];
                }
            }
            if(empty($this->Request->post['category-name-' . $defaultLanguageID])) {
                $error = true;
                $messages[] = $this->Language->get('error_category_name_empty');
            }

            if(!empty($this->Request->post['category-sort-order'])) {
                $data['sort_order'] = (int) $this->Request->post['category-sort-order'];
            }else {
                $data['sort_order'] = 0;
            }
            if(!empty($this->Request->post['category-parent-id'])) {
                if((int) $this->Request->post['category-parent-id'] && $categoryParent = $Category->getCategory((int) $this->Request->post['category-parent-id'])) {
                    $data['parent_id'] = $categoryParent['category_id'];
                    $data['level'] = $categoryParent['level'] + 1;
                }else {
                    $data['parent_id'] = 0;
                    $data['level'] = 0;
                }
            }else {
                $data['parent_id'] = 0;
                $data['level'] = 0;
            }
            /** @var Filter $Filter */
            $Filter = $this->load("Filter", $this->registry);
            $data['filters_id'] = [];
            if(!empty($this->Request->post['category-filters'])) {
                foreach ($this->Request->post['category-filters'] as $filter_id) {
                    if((int) $filter_id && $Filter->getFilterItem((int) $filter_id)) {
                        $data['filters_id'][] = $filter_id;
                    }
                }
            }
            $json = [];

            if(!$error) {

                if($data['sort_order'] == 0) {
                    $rows = $Category->getCategories(array(
                        'sort_order'    => 'sort_order',
                        'order'         => 'DESC',
                        'language_id'   => $this->Language->getLanguageID(),
                        'start'         => 0,
                        'limit'         => 1
                    ));
                    $oldSortOrder = count($rows) > 0 ? $rows[0]['sort_order'] : 0;
                    $data['sort_order'] = $oldSortOrder + 1;
                }
                $Category->insertCategory($data);
                $json['status'] = 1;
                $json['messages'] = [$this->Language->get('success_message')];
                $json['data'] = $data;
                $json['redirect'] = ADMIN_URL . 'product/category/index?token=' . $_SESSION['token'];
            }
            if($error) {
                $json['status'] = 0;
                $json['messages'] = $messages;
            }
            $this->Response->setOutPut(json_encode($json));
        }else {

            $data['Languages'] = $this->Language->getLanguages();
            $data['DefaultLanguageID'] = $this->Language->getDefaultLanguageID();

            $this->Response->setOutPut($this->render('product/category/add', $data));
        }
    }

    public function delete() {
        if(!empty($this->Request->post['categories_id'])) {
            $json = [];
            /** @var Category $Category */
            $Category = $this->load('Category', $this->registry);
            $error = false;
            $this->Database->db->beginTransaction();
            foreach ($this->Request->post['categories_id'] as $category_id) {
                $category = $Category->getCategory((int) $category_id);
                if($category && (int) $category_id) {
                    $Category->deleteCategory((int) $category_id);
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
                $data['Categories'] = $Category->getCategoriesComp(array(
                    'language_id'   => $this->Language->getLanguageID(),
                    'order'         => 'DESC'
                ));
                $json['data'] = $this->render('product/category/category_table', $data);
            }
            $this->Response->setOutPut(json_encode($json));
        }else {
            return new Action('error/notFound', 'web');
        }
    }

    public function getcategories() {
        $data = [];
        $language_id = $this->Language->getLanguageID();
        /** @var Category $Category */
        $Category = $this->load("Category", $this->registry);
        $option = array(
            'language_id'   => $language_id
        );
        if(!empty($this->Request->post['s'])) {
            $option['filter_name']   = trim($this->Request->post['s']);
        }
        $data['Categories'] = $Category->getCategories($option);
        $json = array(
            'status'    => 1,
            'categories'   => $data['Categories']
        );
        $this->Response->setOutPut(json_encode($json));
    }

    public function status() {
        if(isset($this->Request->post['category_id']) && isset($this->Request->post['category_status'])) {
            $category_id = (int) $this->Request->post['category_id'];
            $category_status = (int) $this->Request->post['category_status'] == 1 ? 1 : 0;
            /** @var Category $Category */
            $Category = $this->load("Category", $this->registry);
            $json = [];
            if($category_id &&  $category = $Category->getCategory($category_id)) {
                $Category->editCategory($category_id, array(
                    'status'    => $category_status
                ));
                $json['status'] = 1;
                $json['messages'] = [$this->Language->get('success_message')];
            }else {
                $json['status'] = 0;
                $json['messages'] = [$this->Language->get('error_done')];
            }
            $this->Response->setOutPut(json_encode($json));
            return;
        }
        return new Action('error/notFound', 'web');
    }

    public function edit() {
        $data = [];
        $messages = [];
        $error = false;
        if(isset($this->Request->get[0])) {
            $category_id = (int) $this->Request->get[0];
            /** @var Category $Category */
            $Category = $this->load('Category', $this->registry);
            $categoryTotal = $Category->getCategory($category_id, 'all');
            if($category_id && $categoryTotal) {
                $categoryInfo = [];
                foreach ($categoryTotal as $cat) {
                    $categoryInfo['category_names'][$cat['language_id']] = $cat['name'];
                }
                $categoryInfo['sort_order'] = $categoryTotal[0]['sort_order'];
                $categoryInfo['category_id'] = $categoryTotal[0]['category_id'];
                $categoryInfo['top'] = $categoryTotal[0]['top'];
                $categoryInfo['level'] = $categoryTotal[0]['level'];
                $categoryInfo['sort_order'] = $categoryTotal[0]['sort_order'];
                $categoryInfo['status'] = $categoryTotal[0]['status'];
                if($categoryTotal[0]['parent_id'] == 0) {
                    $categoryInfo['parent_id'] = 0;
                    $categoryInfo['parent_name'] = '';
                }else {
                   $categoryParent = $Category->getCategory($categoryTotal[0]['parent_id']);
                   $categoryInfo['parent_id'] = $categoryParent['category_id'];
                   $categoryInfo['parent_name'] = $categoryParent['name'];
                }
                $categoryInfo['filters'] = $Category->getCategoryFilters($category_id);
                if(isset($this->Request->post['category-post'])) {
                    /** @var Category $Category */
                    $Category = $this->load("Category", $this->registry);
                    $languages = $this->Language->getLanguages();
                    $defaultLanguageID = $this->Language->getDefaultLanguageID();
                    foreach ($languages as $language) {
                        if(!empty($this->Request->post['category-name-' . $language['language_id']])) {
                            $data['category_names'][$language['language_id']] = $this->Request->post['category-name-' . $language['language_id']];
                        }
                    }
                    if(empty($this->Request->post['category-name-' . $defaultLanguageID])) {
                        $error = true;
                        $messages[] = $this->Language->get('error_category_name_empty');
                    }

                    if(!empty($this->Request->post['category-sort-order'])) {
                        $data['sort_order'] = (int) $this->Request->post['category-sort-order'];
                    }else {
                        $data['sort_order'] = 0;
                    }
                    if(!empty($this->Request->post['category-parent-id'])) {
                        if((int) $this->Request->post['category-parent-id'] && $categoryParent = $Category->getCategory((int) $this->Request->post['category-parent-id'])) {
                            $data['parent_id'] = $categoryParent['category_id'];
                            $data['level'] = $categoryParent['level'];
                        }else {
                            $data['parent_id'] = 0;
                            $data['level'] = 0;
                        }
                    }else {
                        $data['parent_id'] = 0;
                        $data['level'] = 0;
                    }
                    /** @var Filter $Filter */
                    $Filter = $this->load("Filter", $this->registry);
                    $data['filters_id'] = [];
                    if(!empty($this->Request->post['category-filters'])) {
                        foreach ($this->Request->post['category-filters'] as $filter_id) {
                            if((int) $filter_id && $Filter->getFilterItem((int) $filter_id)) {
                                $data['filters_id'][] = $filter_id;
                            }
                        }
                    }
                    $json = [];

                    if(!$error) {
                        $add = [];
                        $delete = [];
                        if($data['sort_order'] == 0) {
                            $rows = $Category->getCategories(array(
                                'sort_order'    => 'sort_order',
                                'order'         => 'DESC',
                                'language_id'   => $this->Language->getLanguageID(),
                                'start'         => 0,
                                'limit'         => 1
                            ));
                            $oldSortOrder = count($rows) > 0 ? $rows[0]['sort_order'] : 0;
                            $data['sort_order'] = $oldSortOrder + 1;
                        }
                        if($categoryInfo['sort_order'] == $data['sort_order']) {
                            unset($data['sort_order']);
                        }
                        if($categoryInfo['parent_id'] == $data['parent_id']) {
                            unset($data['parent_id']);
                        }
                        foreach ($this->Language->getLanguages() as $l) {
                            if(isset($categoryInfo['category_names'][$l['language_id']])
                                && isset($data['category_names'][$l['language_id']])
                                && $data['category_names'][$l['language_id']] ==
                                $categoryInfo['category_names'][$l['language_id']]) {
                                unset($data['category_names'][$l['language_id']]);
                            }else if (isset($categoryInfo['category_names'][$l['language_id']])
                                && !isset($data['category_names'][$l['language_id']])) {
                                $delete['category_names'][$l['language_id']] = $categoryInfo['category_names'][$l['language_id']];
                            }else if(!isset($categoryInfo['category_names'][$l['language_id']])
                                && isset($data['category_names'][$l['language_id']])) {
                                $add['category_names'][$l['language_id']] = $data['category_names'][$l['language_id']];
                                unset($data['category_names'][$l['language_id']]);
                            }
                        }
                        if(count($data['category_names']) == 0) {
                            unset($data['category_names']);
                        }
                        if(count($data) > 0) {
                            $Category->editCategory($category_id, $data);
                        }
                        if(count($add) > 0) {
                            $Category->insertCategory($add, $category_id);
                        }
                        if(count($delete) > 0) {
                            $Category->deleteCategory($category_id, $delete);
                        }

                        $json['status'] = 1;
                        $json['messages'] = [$this->Language->get('success_message')];
                        $json['redirect'] = ADMIN_URL . 'product/category/index?token=' . $_SESSION['token'];
                    }
                    if($error) {
                        $json['status'] = 0;
                        $json['messages'] = $messages;
                    }
                    $this->Response->setOutPut(json_encode($json));
                }else {
                    /** @var Filter $Filter */
                    $Filter = $this->load("Filter", $this->registry);
                    $data['CategoryFilters'] = [];
                    foreach ($categoryInfo['filters'] as $filter) {
                        $filterInfo = $Filter->getFilterItem($filter['filter_id']);
                        $data['CategoryFilters'][] = array(
                            'filter_name'   => $filterInfo['name'],
                            'filter_group_name' => $filterInfo['group_name'],
                            'filter_id'     => $filterInfo['filter_id']
                        );
                    }
                    $data['Languages'] = $this->Language->getLanguages();
                    $data['DefaultLanguageID'] = $this->Language->getLanguageID();
                    $data['Category'] = $categoryInfo;
                    $this->Response->setOutPut($this->render('product/category/edit', $data));
                }
                return;
            }
        }
        return new Action('error/notFound', 'web');
    }

}