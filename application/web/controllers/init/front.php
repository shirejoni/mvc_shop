<?php

namespace App\Web\Controller;

use App\model\Category;
use App\system\Controller;

class ControllerInitFront extends Controller {

    public function category() {
        /** @var Category $Category */
        $Category = $this->load("Category", $this->registry);
        $topCategories = $Category->getCategories(array(
            'parent_id' => 0
        ));
        foreach ($topCategories as $index => $topCategory) {
            $subCategories = $Category->getCategoryMenu(array(
                'parent_id' => $topCategory['category_id']
            ));
            $topCategories[$index]['subCategories'] = $subCategories;
        }
        return array(
            'TopCategories' => $topCategories,
        );
    }


}