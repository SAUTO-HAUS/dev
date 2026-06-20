<?php defined( '_DOIT' ) or die( 'Restricted access' );

require_once($_SERVER['DOCUMENT_ROOT'] . '/content/default/includes/contact_form.php');
include_once('tradein_lang.php');

$current_lang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'ro';
?>
<link rel="stylesheet" type="text/css" href="/content/site/css/owl.carousel.min.css">
<link rel="stylesheet" type="text/css" href="/content/site/page/new_pages/tradein/tradein.css?<?=rand(0,999)?>">
<script src="/content/site/js/owl.carousel.min.js"></script>
<script src="/content/site/page/new_pages/tradein/tradein.js?<?=rand(0,999)?>"></script>

<div id="trade-in">
    <div class="trade-in__banner-wrap">
        <div class="trade-in__banner">
            <div class="trade-in__text">
                <h1 class="trade-in__text-title">
                    <?php echo get_translation('banner_title', $current_lang, $lng); ?>
                </h1>
                <div class="trade-in__text-description">
                    <?php echo get_translation('banner_desc', $current_lang, $lng); ?>
                </div>
            </div>
            <div class="trade-in__form" style="background:#fff;border-radius:10px;padding:1.5rem;">
                <?php sauto_contact_form(['lang' => $current_lang, 'source' => 'tradein']); ?>
            </div>
            <div class="trade-in__pic">
                <img src="/content/site/page/new_pages/tradein/tradein-media/tradein_car.png" alt="Trade In">
            </div>
        </div>
    </div>
    <div class="trade-in__advantages">
        <h2 class="trade-in__title"><?php echo get_translation('advantages_title', $current_lang, $lng); ?></h2>
        <div class="trade-in__description">
            <?php echo get_translation('advantages_desc', $current_lang, $lng); ?>
        </div>
        <div class="trade-in__list">
            <div class="trade-in__item">
                <div class="trade-in__item-pic">
                    <img src="/content/site/page/new_pages/tradein/tradein-media/Chronometer.svg" alt="">
                </div>
                <div class="trade-in__item-title"><?php echo get_translation('advantages_item1_title', $current_lang, $lng); ?></div>
                <div class="trade-in__item-description">
                    <?php echo get_translation('advantages_item1_desc', $current_lang, $lng); ?>
                </div>
            </div>
            <div class="trade-in__item">
                <div class="trade-in__item-pic">
                    <img src="/content/site/page/new_pages/tradein/tradein-media/Money.svg" alt="">
                </div>
                <div class="trade-in__item-title"><?php echo get_translation('advantages_item2_title', $current_lang, $lng); ?></div>
                <div class="trade-in__item-description">
                    <?php echo get_translation('advantages_item2_desc', $current_lang, $lng); ?>
                </div>
            </div>
            <div class="trade-in__item">
                <div class="trade-in__item-pic">
                    <img src="/content/site/page/new_pages/tradein/tradein-media/Docs.svg" alt="">
                </div>
                <div class="trade-in__item-title"><?php echo get_translation('advantages_item3_title', $current_lang, $lng); ?></div>
                <div class="trade-in__item-description">
                    <?php echo get_translation('advantages_item3_desc', $current_lang, $lng); ?>
                </div>
            </div>
            <div class="trade-in__item">
                <div class="trade-in__item-pic">
                    <img src="/content/site/page/new_pages/tradein/tradein-media/Sale.svg" alt="">
                </div>
                <div class="trade-in__item-title"><?php echo get_translation('advantages_item4_title', $current_lang, $lng); ?></div>
                <div class="trade-in__item-description">
                    <?php echo get_translation('advantages_item4_desc', $current_lang, $lng); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="trade-in__steps">
        <h2 class="trade-in__title"><?php echo get_translation('steps_title', $current_lang, $lng); ?></h2>
        <div class="trade-in__description">
            <?php echo get_translation('steps_desc', $current_lang, $lng); ?>
        </div>
        <div class="trade-in__steps-list-wrap">
            <div class="trade-in__steps-list">
                <div class="trade-in__steps-item">
                    <div class="trade-in__steps-pic">
                        <img src="/content/site/page/new_pages/tradein/tradein-media/step1.png" alt="">
                    </div>
                    <div class="trade-in__steps-description">
                        <div class="trade-in__steps-title"><?php echo get_translation('steps_item1_title', $current_lang, $lng); ?></div>
                        <div class="trade-in__steps-text">
                            <?php echo get_translation('steps_item1_desc', $current_lang, $lng); ?>
                        </div>
                    </div>
                </div>
                <div class="trade-in__steps-item">
                    <div class="trade-in__steps-pic">
                        <img src="/content/site/page/new_pages/tradein/tradein-media/step2.png" alt="">
                    </div>
                    <div class="trade-in__steps-description">
                        <div class="trade-in__steps-title"><?php echo get_translation('steps_item2_title', $current_lang, $lng); ?></div>
                        <div class="trade-in__steps-text">
                            <?php echo get_translation('steps_item2_desc', $current_lang, $lng); ?>
                        </div>
                    </div>
                </div>
                <div class="trade-in__steps-item">
                    <div class="trade-in__steps-pic">
                        <img src="/content/site/page/new_pages/tradein/tradein-media/step3.png" alt="">
                    </div>
                    <div class="trade-in__steps-description">
                        <div class="trade-in__steps-title"><?php echo get_translation('steps_item3_title', $current_lang, $lng); ?></div>
                        <div class="trade-in__steps-text">
                            <?php echo get_translation('steps_item3_desc', $current_lang, $lng); ?>
                        </div>
                    </div>
                </div>
                <div class="trade-in__steps-item">
                    <div class="trade-in__steps-pic">
                        <img src="/content/site/page/new_pages/tradein/tradein-media/step4.png" alt="">
                    </div>
                    <div class="trade-in__steps-description">
                        <div class="trade-in__steps-title"><?php echo get_translation('steps_item4_title', $current_lang, $lng); ?></div>
                        <div class="trade-in__steps-text">
                            <?php echo get_translation('steps_item4_desc', $current_lang, $lng); ?>
                        </div>
                    </div>
                </div>
                <div class="trade-in__steps-item">
                    <div class="trade-in__steps-pic">
                        <img src="/content/site/page/new_pages/tradein/tradein-media/step5.png" alt="">
                    </div>
                    <div class="trade-in__steps-description">
                        <div class="trade-in__steps-title"><?php echo get_translation('steps_item5_title', $current_lang, $lng); ?></div>
                        <div class="trade-in__steps-text">
                            <?php echo get_translation('steps_item5_desc', $current_lang, $lng); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="trade-in__steps-line"></div>
        </div>
    </div>
    <div class="trade-in__trust">
        <h2 class="trade-in__title"><?php echo get_translation('trust_title', $current_lang, $lng); ?></h2>
        <div class="trade-in__list">
            <div class="trade-in__item">
                <div class="trade-in__item-pic">
                    <img src="/content/site/page/new_pages/tradein/tradein-media/Сertificate.svg" alt="">
                </div>
                <div class="trade-in__item-title"><?php echo get_translation('trust_item1_title', $current_lang, $lng); ?></div>
            </div>
            <div class="trade-in__item">
                <div class="trade-in__item-pic">
                    <img src="/content/site/page/new_pages/tradein/tradein-media/Approved.svg" alt="">
                </div>
                <div class="trade-in__item-title"><?php echo get_translation('trust_item2_title', $current_lang, $lng); ?></div>
            </div>
            <div class="trade-in__item">
                <div class="trade-in__item-pic">
                    <img src="/content/site/page/new_pages/tradein/tradein-media/Cashout.svg" alt="">
                </div>
                <div class="trade-in__item-title"><?php echo get_translation('trust_item3_title', $current_lang, $lng); ?></div>
            </div>
        </div>
    </div>
    <div class="trade-in__reviews">
        <div class="trade-in__reviews-list owl-carousel">
            <div class="trade-in__reviews-item">
                <div class="trade-in__reviews-top">
                    <div class="trade-in__reviews-pic">
                        <img src="/content/site/page/new_pages/tradein/tradein-media/1.jpg" alt="">
                    </div>
                    <div class="trade-in__reviews-info">
                        <div class="trade-in__reviews-title"><?php echo get_translation('reviews_item1_name', $current_lang, $lng); ?></div>
                        <div class="trade-in__reviews-description"><?php echo get_translation('reviews_item1_info', $current_lang, $lng); ?></div>
                    </div>
                </div>
                <div class="trade-in__reviews-text">
                    <?php echo get_translation('reviews_item1_text', $current_lang, $lng); ?>
                </div>
                <div class="trade-in__reviews-quote"></div>
            </div>
            <div class="trade-in__reviews-item">
                <div class="trade-in__reviews-top">
                    <div class="trade-in__reviews-pic">
                        <img src="/content/site/page/new_pages/tradein/tradein-media/2.jpg" alt="">
                    </div>
                    <div class="trade-in__reviews-info">
                        <div class="trade-in__reviews-title"><?php echo get_translation('reviews_item2_name', $current_lang, $lng); ?></div>
                        <div class="trade-in__reviews-description"><?php echo get_translation('reviews_item2_info', $current_lang, $lng); ?></div>
                    </div>
                </div>
                <div class="trade-in__reviews-text">
                    <?php echo get_translation('reviews_item2_text', $current_lang, $lng); ?>
                </div>
                <div class="trade-in__reviews-quote"></div>
            </div>
            <div class="trade-in__reviews-item">
                <div class="trade-in__reviews-top">
                    <div class="trade-in__reviews-pic">
                        <img src="/content/site/page/new_pages/tradein/tradein-media/3.jpg" alt="">
                    </div>
                    <div class="trade-in__reviews-info">
                        <div class="trade-in__reviews-title"><?php echo get_translation('reviews_item3_name', $current_lang, $lng); ?></div>
                        <div class="trade-in__reviews-description"><?php echo get_translation('reviews_item3_info', $current_lang, $lng); ?></div>
                    </div>
                </div>
                <div class="trade-in__reviews-text">
                    <?php echo get_translation('reviews_item3_text', $current_lang, $lng); ?>
                </div>
                <div class="trade-in__reviews-quote"></div>
            </div>
        </div>
    </div>
    <div class="trade-in__faq">
        <h2 class="trade-in__title"><?php echo get_translation('faq_title', $current_lang, $lng); ?></h2>
        <div class="trade-in__description">
            <?php echo get_translation('faq_desc', $current_lang, $lng); ?>
        </div>
        <div class="trade-in__faq-list">
            <div class="trade-in__faq-item">
                <div class="trade-in__faq-title"><?php echo get_translation('faq_item1_title', $current_lang, $lng); ?></div>
                <div class="trade-in__faq-description">
                    <?php echo get_translation('faq_item1_desc', $current_lang, $lng); ?>
                </div>
            </div>
            <div class="trade-in__faq-item">
                <div class="trade-in__faq-title"><?php echo get_translation('faq_item2_title', $current_lang, $lng); ?></div>
                <div class="trade-in__faq-description">
                    <?php echo get_translation('faq_item2_desc', $current_lang, $lng); ?>
                </div>
            </div>
            <div class="trade-in__faq-item">
                <div class="trade-in__faq-title">
                    <?php echo get_translation('faq_item3_title', $current_lang, $lng); ?>
                </div>
                <div class="trade-in__faq-description">
                    <?php echo get_translation('faq_item3_desc', $current_lang, $lng); ?>
                </div>
            </div>
            <div class="trade-in__faq-item">
                <div class="trade-in__faq-title"><?php echo get_translation('faq_item4_title', $current_lang, $lng); ?></div>
                <div class="trade-in__faq-description">
                    <?php echo get_translation('faq_item4_desc', $current_lang, $lng); ?>
                </div>
            </div>
            <div class="trade-in__faq-item">
                <div class="trade-in__faq-title"><?php echo get_translation('faq_item5_title', $current_lang, $lng); ?></div>
                <div class="trade-in__faq-description">
                    <?php echo get_translation('faq_item5_desc', $current_lang, $lng); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="trade-in__request">
        <div class="trade-in__request-info">
            <div class="trade-in__request-title"><?php echo get_translation('form_title', $current_lang, $lng); ?></div>
            <div class="trade-in__request-description">
                <?php echo get_translation('form_desc1', $current_lang, $lng); ?>
            </div>
            <div class="trade-in__request-description">
                <?php echo get_translation('form_desc2', $current_lang, $lng); ?>
            </div>
        </div>
        <div class="trade-in__form" style="background:#fafafa;border-radius:10px;padding:1.5rem;">
            <?php sauto_contact_form(['lang' => $current_lang, 'source' => 'tradein']); ?>
        </div>
    </div>
</div>
