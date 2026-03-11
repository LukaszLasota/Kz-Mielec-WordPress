
//HAMBURGER//

$(document).ready(function () {
    $(".hamburger").click(function () {
        $(this).toggleClass("is-active")
        $('.nav').toggleClass('activ');
  
    });
  });
 
  
// MENU PRZYKLEJONE

  window.onload = function() {
    
    // Variables
    var nav = document.querySelector('.fixed');
    var navTop = nav.offsetTop;
        
     // Functions
    function navFixed(e) {
        if(window.scrollY >= 240 & $(window).width() > 800) {
            nav.classList.add('is-fixed');
        } else {
          nav.classList.remove('is-fixed');
        }
    }

    // Event Listener
    window.addEventListener('scroll', navFixed);
    
  }


  $(function(){
    var fix = document.querySelector('.menu');
    
        $('.menu').each(function(){
            
          if( $(window).width() <= 800){
            $('.menu').addClass('fix');
          } else{
            $('.menu').removeClass('fix');
          }
        });
});
/*
  window.onload = function() {
    
          
     // Functions
    function fix(e) {
        if(window.scrollY >= 1 & $(window).width() <= 800) {
          $('.menu').addClass('fix');
        } else {
          $('.menu').removeClass('fix');
        }
    }
    else {
            nav.classList.remove('is-fixed');
    
    // Event Listener
    window.addEventListener('scroll', fix);
    
  }

  

// SMOOTH SCROLLING

  $(document).ready(function(){
    // Add smooth scrolling to all links
    $("a").on('click', function(event) {
  
      // Make sure this.hash has a value before overriding default behavior
      if (this.hash !== "") {
        // Prevent default anchor click behavior
        //event.preventDefault();
  
        // Store hash
        var hash = this.hash;
  
        // Using jQuery's animate() method to add smooth page scroll
        // The optional number (800) specifies the number of milliseconds it takes to scroll to the specified area
        $('html, body').animate({
          scrollTop: $(hash).offset().top
        }, 700, function(){
     
          // Add hash (#) to URL when done scrolling (default click behavior)
          window.location.hash = hash;
        });
      } // End if
    });
  });
*/








// Picture element HTML5 shiv
document.createElement( "picture" );


  
//jQuery.noConflict();
jQuery(function($) {
 

  // Customize Settings: For more information visit www.blogsynthesis.com/plugins/jquery-smooth-scroll/
   
    // When to show the scroll link
    // higher number = scroll link appears further down the page	
    var upperLimit = 100; 
      
    // Our scroll link element
    var scrollElem = $('a#scroll-to-top');
    
    // Scroll Speed. Change the number to change the speed
    var scrollSpeed = 700;
    
    // Choose your easing effect http://jqueryui.com/resources/demos/effect/easing.html
    var scrollStyle = 'swing';
    
  /****************************************************
   *                                                  *
   *      JUMP TO ANCHOR LINK SCRIPT START            *
   *                                                  *
   ****************************************************/
    
  
  
    // Scroll to top animation on click
    $(scrollElem).click(function(){ 
      $('html, body').animate({scrollTop:0}, scrollSpeed, scrollStyle ); return false; 
    });
  
  /****************************************************
   *                                                  *
   *      JUMP TO ANCHOR LINK SCRIPT START            *
   *                                                  *
   ****************************************************/
  
    $('a[href*="#"]:not([href="#"]):not([href^="#tab"]):not([href^="#collapse"])').click(function()
    {
      if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') 
          || location.hostname == this.hostname) 
      {
        
        var target = $(this.hash),
        headerHeight = $(".primary-header").height() + 5; // Get fixed header height
              
        target = target.length ? target : $('[name=' + this.hash.slice(1) +']');
                
        if (target.length) 
        {
          $('html,body').animate({ scrollTop: target.offset().top -65 }, scrollSpeed, scrollStyle );
          return false;
        }
      }
    });
    
  
  /****************************************************
   *                                                  *
   *   FOLLOW BLOGSYNTHESIS.COM FOR WORDPRESS TIPS    *
   *                                                  *
   ****************************************************/
   
  });
    


