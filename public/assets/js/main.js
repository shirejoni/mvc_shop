$(document).ready(function () {

    /*
    * Category Box Display
    * */
    $('#category').on('mouseenter',function () {
        $('#category #category-box').fadeIn(300);
    });
    $('#category').on('mouseleave',function () {
        $('#category #category-box').fadeOut(300);
    });
    $('#category #main-category > li').on('mouseover',function () {
       $('#category #main-category > li').removeClass('active');
       $(this).addClass('active');
    });



    /*
    * Search Type
    * */
    let searchOpen = false;
    $('#cart-search .search > i').on('click',function () {
        if(searchOpen === false) {
            $('#cart-search #search-field').animate({'width':"240px"},400);
            $('#cart-search #search-field input').focus();
            searchOpen = true;
        }else {
            if($('#cart-search #search-field input').val() === "") {
                $('#cart-search #search-field').animate({'width':"0"},400);
                searchOpen = false;
            }
        }

    });
    $(document).on('mouseup',function () {
            if(searchOpen === true) {
                $('#cart-search .search > i').click();
            }
    });

});