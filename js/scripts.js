( function($) {
  'use strict';

    
   /*-------------------------------------------------------------------------------
	  Detect mobile device 
	-------------------------------------------------------------------------------*/

	var navbar=$('.js-navbar:not(.navbar-fixed)');


	// Hide the loading animation once the page has loaded, or after 2.5 seconds at most
	// so a slow image never keeps visitors waiting.
	var hideLoader = function(){ $('.loader').fadeOut(600); };
	$(window).on('load', hideLoader);
	setTimeout(hideLoader, 2500);


	/*-------------------------------------------------------------------------------
	  Navbar 
	-------------------------------------------------------------------------------*/



	navbar.affix({
	  offset: {
	    top: 50
	  }
	});


	navbar.on('affix.bs.affix', function() {
		if (!navbar.hasClass('affix')){
			navbar.addClass('animated slideInDown');
		}
	});

	navbar.on('affixed-top.bs.affix', function() {
	  	navbar.removeClass('animated slideInDown');
	  	
	});

	$('.nav-mobile-list li a[href="#"]').on('click',function(){
		$(this).closest('li').toggleClass('current');
		$(this).closest('li').children('ul').slideToggle(200);
		return false;
	});



	/*-------------------------------------------------------------------------------
	  Menu
	-------------------------------------------------------------------------------*/



	$('.navbar-toggle').on('click',function(){
		$('body').removeClass('menu-is-closed').addClass('menu-is-opened');
	});

	$('.close-menu, .click-capture').on('click', function(){
		$('body').removeClass('menu-is-opened').addClass('menu-is-closed');
		$('.menu-list ul').slideUp(300);
	});


	/*-------------------------------------------------------------------------------
	  Owl Carousel
	-------------------------------------------------------------------------------*/


	if ($('.owl-carousel').length > 0){

	   $(".review-carousel").owlCarousel({
			responsive:{
		       0:{
		            items:1
		        },
		        720:{
		            items:1,
		            
		        },
		        1280:{
		            items:1
		        }
		    },
		    responsiveRefreshRate:0,
			nav:true,
			navText:[],
			animateIn: 'fadeIn',
		 	dots:false
		});

	   $(".project-carousel").owlCarousel({
			responsive:{
		       0:{
		            items:1
		        },
		        720:{
		            items:1,
		            
		        },
		        1280:{
		            items:1
		        }
		    },
		    autoHeight:true,
			nav:true,
			navText:[],
			loop:true,
			responsiveRefreshRate:0,
			smartSpeed:500,
		 	dots:false
		});

	}




	/*-------------------------------------------------------------------------------
	  Full screen sections 
	-------------------------------------------------------------------------------*/



    if ($('.pagepiling').length > 0){

      	$('.pagepiling').pagepiling({
    		scrollingSpeed: 280,
		    loopBottom:true,
		    // index.php lists the anchors of the sections switched on in the admin panel.
		    anchors: window.SECTION_ANCHORS || ['page1', 'page2', 'page3', 'page4', 'page5', 'page6', 'page7'],
		    afterLoad: function(anchorLink, index){
	            if ( $('.pp-section.active').scrollTop() > 0 ){
	            	$('.navbar').removeClass('navbar-white');
	            }
	            else{
	            	$('.navbar').addClass('navbar-white');
	            }
	            
  			}
		});



		/*-------------------------------------------------------------------------------
		   Scroll into sections 
		/-------------------------------------------------------------------------------*/



		$('.pp-scrollable').on('scroll', function () {
    		var scrollTop =$(this).scrollTop();
    		if (scrollTop > 0 ) {
    			$('.navbar-2').removeClass('navbar-white');
    		}
    		else{
    			$('.navbar-2').addClass('navbar-white');
    		}
		});



		/*-------------------------------------------------------------------------------
		   Scroller navigation
		/-------------------------------------------------------------------------------*/



		$('#pp-nav').remove().appendTo('body').addClass('white right-boxed hidden-xs');

		$('.pp-nav-up').on('click', function(){
			$.fn.pagepiling.moveSectionUp();
		});

		$('.pp-nav-down').on('click', function(){
			$.fn.pagepiling.moveSectionDown();
		});
    } 



    /*-------------------------------------------------------------------------------
	  Change bacgkround on project section
	-------------------------------------------------------------------------------*/



    $('.project-box').on('mouseover',function(){
    	var index = $('.project-box').index(this);
    	$('.bg-changer .section-bg').removeClass('active').eq(index).addClass('active');
    });



	/*-------------------------------------------------------------------------------
	  Ajax Forms
	-------------------------------------------------------------------------------*/



	if ($('.js-form').length) {
		$('.js-form').each(function(){
			$(this).validate({
				errorClass: 'error wobble-error',
			    submitHandler: function(form){
			    	var $button = $(form).find('button[type="submit"]');
			    	var buttonText = $button.text();
			    	$button.prop('disabled', true).text('Sending...');
		        	$.ajax({
			            type: "POST",
			            url: "contact.php",
			            data: $(form).serialize(),
			            success: function() {
			            	$('#error').modal('hide');
		                	$('#success').modal('show');
		                	form.reset();
		                },

		                error: function(){
		                	$('#success').modal('hide');
			                $('#error').modal('show');
			            },

			            complete: function(){
			            	$button.prop('disabled', false).text(buttonText);
			            }
			        });
			    }
			});
		});
	}

	
	


})(jQuery);
