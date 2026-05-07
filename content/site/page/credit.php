<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<!--Plugin CSS file with desired skin-->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.1/css/ion.rangeSlider.min.css"/>


<!--jQuery-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

<!--Plugin JavaScript file-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.1/js/ion.rangeSlider.min.js"></script>

<style>
#credit .m_img {
    height: 15rem;
}
.payment-amount {
    color: #666666;
}
.payment-number {
    color: #ff0000;
    font-weight: bold;
}

/* H1 title style */
#credit h1 {
    color: #e2001a;
    margin-bottom: 30px;
    text-align: center;
}

/* H2 styles */
#credit h2 {
    color: #b20016; /* H2 color */
    font-weight: bold;
}

/* Client stories section title */
.stories-slider h2 {
    text-align: center;
    margin-bottom: 25px;
    font-weight: bold;
}

/* Horizontal layout styles */
.content-1, .content-2, .content-3, .content-4 {
    display: flex;
    align-items: center;
    width: 100%;
    margin-bottom: 30px;
}

.content-1 img, .content-2 img, .content-3 img, .content-4 img,
.text-content {
    flex: 0 0 50%;
    max-width: 50%;
}

/* Desktop layout - alternating pattern */
.content-1 img { order: 1; }
.content-1 .text-content { order: 2; }

.content-2 img { order: 2; }
.content-2 .text-content { order: 1; }

.content-3 img { order: 1; }
.content-3 .text-content { order: 2; }

.content-4 img { order: 2; }
.content-4 .text-content { order: 1; }

.content-1 img, .content-2 img, .content-3 img, .content-4 img {
    object-fit: cover;
    border-radius: 8px;
    display: block;
    height: 300px;
}

.text-content {
    padding-left: 5%;
}

/* Text spacing */
.content-1 .text-content, .content-3 .text-content {
    padding-left: 5%;
}

.content-2 .text-content, .content-4 .text-content {
    padding-right: 5%;
}

/* Responsive design media queries */
@media (max-width: 767px) {
    .content-1, .content-2, .content-3, .content-4 {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    .content-1 > *, .content-2 > *, .content-3 > *, .content-4 > * {
        width: 100% !important;
        max-width: 100% !important;
        margin-bottom: 20px;
    }

    /* Mobile order - images always first */
    .content-1 img, .content-2 img, .content-3 img, .content-4 img { order: 1; }
    .content-1 .text-content, .content-2 .text-content, .content-3 .text-content, .content-4 .text-content { order: 2; }

    /* Hide navigation arrows on mobile */
    #story-prev, #story-next {
        display: none !important;
    }
}
</style>

<div id="credit">
    <img class="m_img" src="/media/images/site/v2/serv_credit.svg" />
    
    <!-- Credit calculator section -->
    <div class="spc_bx d_right_b spc_bx_calc_b"> 
        <div class="calc_head"> <?php echo $lng['w']['calc_title']; ?> </div>
        
        <div class="calc_block_sum">
            <div class="calc_inpt_cont">
                <div class="calc_ipt_tl">
                    <?php echo $lng['w']['calc_title_sum_tl']; ?>
                </div>
                <div class="calc_inpt_blk">
                    <input type="text" id="view_suma_creditului" class="clacl_inpt_vie">
                </div>
            </div>
            <input type="text" id="suma-creditului">
        </div>
            
        <div class="calc_block_terms">
            <div class="calc_inpt_cont">
                <div class="calc_ipt_tl">
                    <?php echo $lng['w']['calc_title_term_tl']; ?>
                </div>
                <div class="calc_inpt_blk">
                    <input type="text" id="view_termen_creditului" class="clacl_inpt_vie">
                </div>
            </div>
            <input type="text" id="termen-creditului" name="termen_creditului">
        </div>
        
        <div style="clear: both"> </div>
        
        <div class="calc_btt_word">
            <div class="calc_btt_left">
                <?php echo $lng['w']['calc_title_rata']; ?>
            </div>
            <div class="calc_btt_right">
                <div class="calc_btt_r1">
                    <span class="calc_btt_r1_nrl"> 24 </span> <?php echo $lng['w']['calc_title_luni']; ?>
                </div>
                <div class="calc_btt_r2">
                   <div class="payment-amount"><?php echo $lng['w']['calc_title_plata']; ?> <span class="payment-number calc_btt_r2_nrl">0</span> <?php echo $lng['w']['calc_title_plata2']; ?> <span class="payment-number calc_btt_r3_nrl">0</span></div>
                </div>
            </div>
        </div>


    </div><!-- End of calculator section -->
    
    <div style="clear: both; margin-bottom: 30px;"></div>

    <!-- Main content section -->
    <div class="credit-content">
        <?php echo $lang_xtra_page['credit']; ?>
    </div>
        
        <div style="clear: both"> </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        let updateRateTimeout;
        let userIsEditing = false;

        var $input_suma_creditului = $("#view_suma_creditului");
        // Credit amount slider
        const sliderSuma = $("#suma-creditului").ionRangeSlider({
            skin: "round",
            min: 2000,
            max: 50000,
            from: 2000,
            step: 500,
            onStart: function(data) {
                if (!$input_suma_creditului.val()) {
                    $input_suma_creditului.prop("value", data.from);
                }
            },
            onChange: function (data) {
                if (!userIsEditing) {
                    $input_suma_creditului.prop("value", data.from);
                    clearTimeout(updateRateTimeout);
                    updateRateTimeout = setTimeout(updateRate, 300);
                }
            }
        }).data("ionRangeSlider");

        var $input_termen_creditului = $("#view_termen_creditului");
        // Credit term slider
        const sliderTermen = $("#termen-creditului").ionRangeSlider({
            skin: "round",
            min: 6,
            max: 60,
            from: 60,
            step: 1,
            onStart: function(data) {
                if (!$input_termen_creditului.val()) {
                    $input_termen_creditului.prop("value", data.from);
                }
            },
            onChange: function (data) {
                if (!userIsEditing) {
                    $input_termen_creditului.prop("value", data.from);
                    clearTimeout(updateRateTimeout);
                    updateRateTimeout = setTimeout(updateRate, 300);
                }
            }
        }).data("ionRangeSlider");

        // Manual input - AMOUNT
        $input_suma_creditului.on("focus", function() {
            userIsEditing = true;
            $(this).select();
        }).on("blur", function() {
            userIsEditing = false;
            let val = parseInt($(this).val(), 10);
            if (isNaN(val)) val = 2000;
            val = Math.max(2000, Math.min(50000, val));
            val = Math.round(val / 500) * 500;
            $(this).val(val);
            sliderSuma.update({ from: val });
            updateRate();
        }).on("input", function() {
            let val = parseInt($(this).val(), 10);
            if (!isNaN(val)) {
                val = Math.max(2000, Math.min(50000, val));
                val = Math.round(val / 500) * 500;
                sliderSuma.update({ from: val });
                updateRate();
            }
        });

        // Manual input - TERM
        $input_termen_creditului.on("focus", function() {
            userIsEditing = true;
            $(this).select();
        }).on("blur", function() {
            userIsEditing = false;
            let val = parseInt($(this).val(), 10);
            if (isNaN(val)) val = 6;
            val = Math.max(6, Math.min(60, val));
            $(this).val(val);
            sliderTermen.update({ from: val });
            updateRate();
        }).on("input", function() {
            let val = parseInt($(this).val(), 10);
            if (!isNaN(val)) {
                val = Math.max(6, Math.min(60, val));
                sliderTermen.update({ from: val });
                updateRate();
            }
        });

        function updateRate() {
            // Get values directly from input fields
            const suma = parseInt($input_suma_creditului.val(), 10);
            const termen = parseInt($input_termen_creditului.val(), 10);
            
            // Verificăm dacă valorile sunt valide
            if (isNaN(suma) || isNaN(termen)) {
                return; // Evităm calculul cu valori invalide
            }
            
            $('.calc_btt_r1_nrl').text(termen);

            // Calcul pentru rata minimă (9.2%)
            const dobanda_min = 9.2; // procente
            const rata_lunara_min = dobanda_min / 1200; // convertim în decimal și lunar (9.2/100/12)
            const pmtMin = suma * rata_lunara_min / (1 - Math.pow(1 + rata_lunara_min, -termen));
            
            // Calcul pentru rata maximă (24%)
            const dobanda_max = 24; // procente
            const rata_lunara_max = dobanda_max / 1200; // convertim în decimal și lunar (24/100/12)
            const pmtMax = suma * rata_lunara_max / (1 - Math.pow(1 + rata_lunara_max, -termen));

            // Rotunjire la numere întregi - folosim aceeași metodă pentru ambele
            const rata_min_final = Math.floor(pmtMin);
            const rata_max_final = Math.floor(pmtMax);

            // Display rates
            $('.calc_btt_r2_nrl').text(rata_min_final);
            $('.calc_btt_r3_nrl').text(rata_max_final);
        }

        // Set initial values
        const initialAmount = 2000;
        $input_suma_creditului.val(initialAmount);
        $input_termen_creditului.val(60);
        
        // Update sliders for synchronization
        sliderSuma.update({ from: initialAmount });
        sliderTermen.update({ from: 60 });
        
        // Delay first calculation until components are initialized
        setTimeout(updateRate, 100);
        
        // Calculate initial rate
        updateRate();
    });
</script>

<script>
// Client stories slider script
document.addEventListener('DOMContentLoaded', function() {
    // Încercăm să găsim containerul și poveștile
    var storiesContainer = document.querySelector('.client-stories');
    if (!storiesContainer) {
        console.error('Nu s-a găsit containerul pentru povești');
        return;
    }

    // Înfășurăm containerul de povești într-un container slider dacă nu este deja
    var sliderContainer;
    if (!storiesContainer.parentElement.classList.contains('stories-slider')) {
        sliderContainer = document.createElement('div');
        sliderContainer.className = 'stories-slider';
        
        // Creăm un titlu pentru slider
        var sliderTitle = document.createElement('h2');
        sliderTitle.textContent = 'Poveștile clienților noștri';
        sliderTitle.style.textAlign = 'center';
        sliderTitle.style.marginBottom = '30px';
        
        // Adăugăm titlul și apoi containerul de povești în containerul slider
        storiesContainer.parentNode.insertBefore(sliderContainer, storiesContainer);
        sliderContainer.appendChild(sliderTitle);
        sliderContainer.appendChild(storiesContainer);
    } else {
        sliderContainer = storiesContainer.parentElement;
    }
    
    // Obținem toate poveștile
    var stories = storiesContainer.querySelectorAll('.story');
    if (stories.length === 0) {
        console.error('Nu s-au găsit povești în container');
        return;
    }
    
    // Aplicăm stiluri inline pentru a ne asigura că funcționează
    storiesContainer.style.width = '100%';
    storiesContainer.style.position = 'relative';
    
    // Stiluri pentru povești
    stories.forEach(function(story) {
        story.style.backgroundColor = '#fff';
        story.style.padding = '30px';
        story.style.borderRadius = '10px';
        story.style.boxShadow = '0 5px 15px rgba(0,0,0,0.08)';
        story.style.borderTop = '4px solid #e2001a';
        story.style.position = 'relative';
        story.style.display = 'none';
        story.style.maxWidth = '800px';
        story.style.margin = '0 auto';
        story.style.textAlign = 'center';
        
        // Stiluri pentru titlurile și paragrafele din povești
        var storyTitle = story.querySelector('h3');
        if (storyTitle) {
            storyTitle.style.fontSize = '1.5rem';
            storyTitle.style.marginBottom = '15px';
            storyTitle.style.color = '#e2001a';
        }
        
        var storyParagraph = story.querySelector('p');
        if (storyParagraph) {
            storyParagraph.style.color = '#555';
            storyParagraph.style.fontStyle = 'italic';
            storyParagraph.style.lineHeight = '1.6';
        }
    });
    
    // Facem prima poveste vizibilă
    if (stories.length > 0) {
        stories[0].style.display = 'block';
        stories[0].classList.add('active');
    }
    
    // Creăm containerul pentru punctele de navigare
    var dotsContainer = document.createElement('div');
    dotsContainer.className = 'story-dots-container';
    // Stilăm containerul de puncte
    dotsContainer.style.display = 'flex';
    dotsContainer.style.justifyContent = 'center';
    dotsContainer.style.marginTop = '30px';
    dotsContainer.style.gap = '8px';
    dotsContainer.style.flexWrap = 'nowrap';
    dotsContainer.style.width = '100%';
    dotsContainer.style.alignItems = 'center';
    
    // Creăm punctele pentru fiecare poveste
    for (var i = 0; i < stories.length; i++) {
        var dot = document.createElement('div');
        dot.className = 'story-dot';
        dot.style.width = '10px';
        dot.style.height = '10px';
        dot.style.borderRadius = '50%';
        dot.style.backgroundColor = i === 0 ? '#e2001a' : '#ddd';
        dot.style.cursor = 'pointer';
        dot.style.transition = 'background-color 0.3s ease';
        dot.style.display = 'inline-block';
        dot.style.margin = '0 5px';
        dot.dataset.index = i;
        dot.addEventListener('click', function() {
            changeSlide(parseInt(this.dataset.index));
        });
        dotsContainer.appendChild(dot);
    }
    
    // Adăugăm punctele la container
    sliderContainer.appendChild(dotsContainer);
    
    // Variabilă pentru slide-ul curent
    var currentSlide = 0;
    var dots = dotsContainer.querySelectorAll('div');
    
    // Funcții delegate pentru navigare - definite o singură dată în afara altor funcții
    function goToPrevSlide() {
        var prevSlide = (currentSlide - 1 + stories.length) % stories.length;
        changeSlide(prevSlide);
    }
    
    function goToNextSlide() {
        var nextSlide = (currentSlide + 1) % stories.length;
        changeSlide(nextSlide);
    }
    
    // Funcția pentru schimbarea slide-ului OPTIMIZATĂ
    function changeSlide(slideIndex) {
        // Prevenire re-render dacă slide-ul actual este cel selectat
        if (currentSlide === slideIndex) {
            return;
        }
    
        // Actualizăm index-ul curent
        currentSlide = slideIndex;
        
        // Ascundem toate slide-urile și resetăm clase
        for (var i = 0; i < stories.length; i++) {
            stories[i].style.display = 'none';
            stories[i].classList.remove('active');
        }
        
        // Resetăm dot-urile de navigare
        for (var i = 0; i < dots.length; i++) {
            dots[i].style.backgroundColor = '#ccc';
        }
        
        // Afișăm noul slide și marcăm dot-ul activ
        stories[slideIndex].style.display = 'block';
        stories[slideIndex].classList.add('active');
        dots[slideIndex].style.backgroundColor = '#e2001a';
        
        // OPTIMIZAT - Mutăm săgețile fără a duplica event listeners
        var buttons = document.querySelector('.slider-nav-buttons');
        if (buttons) {
            buttons.remove();
        }
        
        // Adăugăm săgețile la noul slide activ
        stories[slideIndex].insertAdjacentHTML('beforeend', navButtonsHTML);
        
        // Atașăm evenimentele folosind funcții delegate - sunt adăugate doar o dată per element
        document.getElementById('story-prev').onclick = goToPrevSlide;
        document.getElementById('story-next').onclick = goToNextSlide;
    }
    
    // NICI O SCHIMBARE AUTOMATĂ - Eliminăm complet orice funcție și interval pentru schimbarea automată
    
    // Adăugăm navigarea cu tastatura (săgeți stânga/dreapta)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft') {
            // Mergem la slide-ul anterior
            var prevSlide = (currentSlide - 1 + stories.length) % stories.length;
            changeSlide(prevSlide);
        } else if (e.key === 'ArrowRight') {
            // Mergem la slide-ul următor
            var nextSlideIndex = (currentSlide + 1) % stories.length;
            changeSlide(nextSlideIndex);
        }
    });
    
    // Adăugăm și butoane de navigare vizibile (săgeți stânga/dreapta)
    var prevButton = document.createElement('div');
    prevButton.style.position = 'absolute';
    prevButton.style.left = '-20px';
    prevButton.style.top = '50%';
    prevButton.style.transform = 'translateY(-50%)';
    prevButton.style.backgroundColor = '#e2001a';
    prevButton.style.color = 'white';
    prevButton.style.borderRadius = '50%';
    prevButton.style.width = '40px';
    prevButton.style.height = '40px';
    prevButton.style.display = 'flex';
    prevButton.style.alignItems = 'center';
    prevButton.style.justifyContent = 'center';
    prevButton.style.cursor = 'pointer';
    prevButton.style.boxShadow = '0 0 0 2px white, 0 3px 8px rgba(0,0,0,0.2)';
    prevButton.style.zIndex = '100';
    prevButton.style.fontSize = '24px';
    prevButton.style.fontWeight = 'bold';
    prevButton.innerHTML = '&#10094;'; // Săgeată stânga
    prevButton.addEventListener('click', function() {
        clearInterval(slideInterval);
        var prevSlide = (currentSlide - 1 + stories.length) % stories.length;
        changeSlide(prevSlide);
        slideInterval = setInterval(nextSlide, 5000);
    });
    
    var nextButton = document.createElement('div');
    nextButton.style.position = 'absolute';
    nextButton.style.right = '-20px';
    nextButton.style.top = '50%';
    nextButton.style.transform = 'translateY(-50%)';
    nextButton.style.backgroundColor = '#e2001a';
    nextButton.style.color = 'white';
    nextButton.style.borderRadius = '50%';
    nextButton.style.width = '40px';
    nextButton.style.height = '40px';
    nextButton.style.display = 'flex';
    nextButton.style.alignItems = 'center';
    nextButton.style.justifyContent = 'center';
    nextButton.style.cursor = 'pointer';
    nextButton.style.boxShadow = '0 0 0 2px white, 0 3px 8px rgba(0,0,0,0.2)';
    nextButton.style.zIndex = '100';
    nextButton.style.fontSize = '24px';
    nextButton.style.fontWeight = 'bold';
    nextButton.innerHTML = '&#10095;'; // Right arrow
    nextButton.addEventListener('click', function() {
        clearInterval(slideInterval);
        var nextSlideIndex = (currentSlide + 1) % stories.length;
        changeSlide(nextSlideIndex);
        slideInterval = setInterval(nextSlide, 5000);
    });
    
    // Navigation arrows inside active story window
    console.log('Implementing navigation arrows inside active window...');
    
    // Set uniform height for all stories
    var maxHeight = 0;
    stories.forEach(function(story) {
        // Set minimum height for stories
        story.style.minHeight = '300px';
    });
    
    // Create navigation arrows inside active window
    var navButtonsHTML = `
        <div class="slider-nav-buttons">
            <div class="slider-arrow prev" id="story-prev" style="position: absolute; left: 10px; top: 20px; width: 40px; height: 40px; background-color: #e2001a; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 0 0 2px white, 0 3px 8px rgba(0,0,0,0.3); font-size: 24px; font-weight: bold; z-index: 1000;">&larr;</div>
            <div class="slider-arrow next" id="story-next" style="position: absolute; right: 10px; top: 20px; width: 40px; height: 40px; background-color: #e2001a; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 0 0 2px white, 0 3px 8px rgba(0,0,0,0.3); font-size: 24px; font-weight: bold; z-index: 1000;">&rarr;</div>
        </div>
    `;
    
    // Insert navigation arrows into active story container
    for (var i = 0; i < stories.length; i++) {
        // Set relative position for arrow placement
        stories[i].style.position = 'relative';
    }
    
    // Add arrows to first visible story
    stories[0].insertAdjacentHTML('beforeend', navButtonsHTML);
    
    // Add click events to navigation buttons
    document.getElementById('story-prev').onclick = goToPrevSlide;
    document.getElementById('story-next').onclick = goToNextSlide;

    // Add touch swipe functionality for mobile
    let touchStartX = 0;
    storiesContainer.addEventListener('touchstart', function(e) {
        touchStartX = e.touches[0].clientX;
    });

    storiesContainer.addEventListener('touchend', function(e) {
        let touchEndX = e.changedTouches[0].clientX;
        let swipeDistance = touchEndX - touchStartX;

        if (Math.abs(swipeDistance) > 50) {
            if (swipeDistance > 0) {
                goToPrevSlide();
            } else {
                goToNextSlide();
            }
        }
    });
    
    console.log('Navigation arrows placed inside active story window');
});
</script>
