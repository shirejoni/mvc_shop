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
    * Slider
    * */
    function Slider(boxSliderID) {
        let $ = this;
        this.element = document.getElementById(boxSliderID);
        this.slides = [];
        this.slider = '';
        this.buttons = [];
        this.timeOut = '';
        this.current = 0;


        this.move = function (index) {

            $.slider.style.left = "-"+ (index * 100) +"%";
            if(index === ($.slides.length - 1)) {
                $.buttons[1].style.opacity = "0.3";
            }else {
                $.buttons[1].style.opacity = "1";
            }
            if(index === 0) {
                $.buttons[0].style.opacity = "0.3";
            }else {
                $.buttons[0].style.opacity = "1";
            }
            $.current = index;
        };

        this.loop = function () {
            clearTimeout($.timeOut);
            $.timeOut = setTimeout($.loop, 3000);
            if($.current === ($.slides.length - 1)) {
                $.move(0);
            }else {
                $.move($.current + 1);
            }

        };

        this.init = function () {
            this.slider = this.element.querySelector(".slider");
            this.slides = this.element.querySelectorAll(".slide");
            this.buttons = this.element.querySelectorAll(".slider-buttons > div");
            $.buttons[0].style.opacity = "0.3";
            let left = 100;
            let i = 0;
            $.slides.forEach(function (value) {
                value.style.left = (left * i) + "%";
                i += 1;
            });

            $.timeOut = setTimeout($.loop,3000);

            this.initButton();
        };

        this.initButton = function () {
            this.buttons[0].addEventListener('click', function () {
                clearTimeout($.timeOut);
                $.timeOut = setTimeout($.loop, 3000);
                if($.current !== 0) {
                    $.move($.current - 1);
                }
            }, false);
            this.buttons[1].addEventListener('click', function () {
                clearTimeout($.timeOut);
                $.timeOut = setTimeout($.loop, 3000);
                if($.current !== ($.slides.length - 1)) {
                    $.move($.current + 1);
                }
            }, false);
        };

        this.init();
    }


    let mainSlider = new Slider("slider");

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