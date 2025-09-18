<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>
<link rel="stylesheet" type="text/css" href="/content/site/page/new_pages/sale/sale.css?<?=rand(0,999)?>">
<script defer src="/content/site/page/new_pages/sale/sale.js?<?=rand(0,999)?>"></script>

<div class="sale-page">
    <section class="sale-section sale-hero sale-section--accent sale-animate">
        <div class="sale-hero__content">
            <div class="sale-hero__eyebrow">Sauto Haus</div>
            <h1>Продажа авто на реализацию</h1>
            <h2>Продайте свой автомобиль через Sauto Haus – быстро, выгодно и без хлопот</h2>
            <p>Вы привозите машину – мы берём всё на себя: оценку, рекламу, показы, оформление сделки. Деньги получаете вы.</p>
            <p>Продавать машину самому – значит тратить время, общаться с десятками «смотрящих», рисковать с документами и ценой. Мы решаем все эти задачи за вас: профессионально выставляем автомобиль, приводим покупателей и оформляем сделку так, чтобы вы остались в плюсе и без лишних нервов.</p>
            <div class="sale-hero__cta">
                <button class="sale-btn sale-btn--primary sale-btn--pulse" type="button" data-sale-form-trigger="#sale-form">Оставить заявку</button>
                <div class="sale-hero__note">Работаем официально и по-честному — как всегда у Sauto Haus.</div>
            </div>
        </div>
        <div class="sale-hero__visual">
            <div class="sale-hero__glow"></div>
            <div class="sale-hero__rings sale-floating"></div>
            <div class="sale-hero__car" aria-hidden="true">
                <svg viewBox="0 0 320 160" role="img" focusable="false">
                    <defs>
                        <linearGradient id="saleCarBody" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#ff3131" />
                            <stop offset="50%" stop-color="#e2001a" />
                            <stop offset="100%" stop-color="#9b0011" />
                        </linearGradient>
                        <linearGradient id="saleCarGlass" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#d9f1ff" />
                            <stop offset="100%" stop-color="#8bc5ff" />
                        </linearGradient>
                    </defs>
                    <path d="M40 100 C70 30 250 30 280 100 L300 100 Q310 100 310 110 L310 120 Q310 130 300 130 L280 130 Q270 145 240 145 Q210 145 200 130 L120 130 Q110 145 80 145 Q50 145 40 130 L20 130 Q10 130 10 120 L10 110 Q10 100 20 100 Z" fill="url(#saleCarBody)" opacity="0.92" />
                    <path d="M80 65 Q120 30 220 30 Q255 30 260 70 L80 70" fill="url(#saleCarGlass)" opacity="0.88" />
                    <circle cx="90" cy="135" r="22" fill="#1d1d1f" />
                    <circle cx="90" cy="135" r="12" fill="#f6f6f6" />
                    <circle cx="230" cy="135" r="22" fill="#1d1d1f" />
                    <circle cx="230" cy="135" r="12" fill="#f6f6f6" />
                    <path d="M55 112 Q75 105 95 112" stroke="#ffd300" stroke-width="6" stroke-linecap="round" fill="none" />
                    <path d="M195 112 Q215 105 235 112" stroke="#ffd300" stroke-width="6" stroke-linecap="round" fill="none" />
                    <path d="M40 102 L280 102" stroke="#ffffff" stroke-width="4" stroke-linecap="round" opacity="0.35" />
                </svg>
            </div>
            <div class="sale-hero__badge">100% контроль сделки</div>
            <div class="sale-hero__badge sale-hero__badge--alt">+ доверие клиентов</div>
        </div>
    </section>

    <section class="sale-section sale-advantages sale-animate" id="sale-advantages">
        <header class="sale-section__head">
            <h3>Преимущества</h3>
            <div class="sale-section__accent"></div>
        </header>
        <div class="sale-card-grid sale-scrollable" data-sale-scrollable>
            <article class="sale-card sale-card--icon sale-animate">
                <div class="sale-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 64 64" focusable="false">
                        <circle cx="32" cy="32" r="30" fill="rgba(226,0,26,0.1)" />
                        <path d="M18 30 L46 18 L40 46 L34 36 L18 30 Z" fill="#e2001a" stroke="#ff6161" stroke-width="2" stroke-linejoin="round" />
                        <circle cx="26" cy="32" r="4" fill="#fff" />
                    </svg>
                </div>
                <p>- Реальная рыночная цена – продаём дороже, чем перекупы.</p>
            </article>
            <article class="sale-card sale-card--icon sale-animate">
                <div class="sale-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 64 64" focusable="false">
                        <rect x="12" y="14" width="40" height="36" rx="8" fill="rgba(226,0,26,0.12)" />
                        <path d="M20 28 H44" stroke="#e2001a" stroke-width="3" stroke-linecap="round" />
                        <path d="M20 36 H44" stroke="#e2001a" stroke-width="3" stroke-linecap="round" />
                        <path d="M24 44 H40" stroke="#e2001a" stroke-width="3" stroke-linecap="round" />
                    </svg>
                </div>
                <p>- Полная прозрачность: договор, официальные расчёты.</p>
            </article>
            <article class="sale-card sale-card--icon sale-animate">
                <div class="sale-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 64 64" focusable="false">
                        <path d="M32 8 L52 18 V34 C52 46 44 56 32 58 C20 56 12 46 12 34 V18 L32 8 Z" fill="rgba(0,46,102,0.08)" stroke="#002e66" stroke-width="2" />
                        <path d="M24 32 L30 38 L42 24" stroke="#e2001a" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <p>- Ваш автомобиль в безопасности – стоит у нас на охраняемой площадке.</p>
            </article>
            <article class="sale-card sale-card--icon sale-animate">
                <div class="sale-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 64 64" focusable="false">
                        <circle cx="20" cy="24" r="10" fill="rgba(226,0,26,0.08)" />
                        <circle cx="44" cy="40" r="12" fill="rgba(0,46,102,0.08)" />
                        <path d="M14 48 C18 42 28 34 34 30 C40 26 48 22 54 24" stroke="#e2001a" stroke-width="4" fill="none" stroke-linecap="round" />
                    </svg>
                </div>
                <p>- Мы берём на себя рекламу, звонки, показы.</p>
            </article>
            <article class="sale-card sale-card--icon sale-animate">
                <div class="sale-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 64 64" focusable="false">
                        <rect x="10" y="18" width="44" height="28" rx="14" fill="rgba(226,0,26,0.08)" />
                        <path d="M18 32 H46" stroke="#e2001a" stroke-width="4" stroke-linecap="round" />
                        <path d="M24 40 L32 48 L40 40" stroke="#002e66" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <p>- Быстрое оформление: деньги получаете сразу после продажи.</p>
            </article>
        </div>
    </section>

    <section class="sale-section sale-process sale-animate" id="sale-process">
        <header class="sale-section__head">
            <h3>Как это работает</h3>
        </header>
        <div class="sale-timeline">
            <ol class="sale-timeline__list sale-scrollable" data-sale-scrollable>
                <li class="sale-timeline__item sale-animate"><span>1. Вы привозите автомобиль к нам в автосалон.</span></li>
                <li class="sale-timeline__item sale-animate"><span>2. Мы проводим визуальный осмотр и тест-драйв.</span></li>
                <li class="sale-timeline__item sale-animate"><span>3. Подписываем договор на оказание услуги.</span></li>
                <li class="sale-timeline__item sale-animate"><span>4. Авто проходит мойку и фотосъёмку.</span></li>
                <li class="sale-timeline__item sale-animate"><span>5. Размещаем объявления на 999.md, Facebook, Instagram, Telegram, TikTok.</span></li>
                <li class="sale-timeline__item sale-animate"><span>6. Ведём переговоры и показываем автомобиль покупателям.</span></li>
                <li class="sale-timeline__item sale-animate"><span>7. Организуем сделку и передаём вам деньги.</span></li>
            </ol>
            <div class="sale-timeline__line" aria-hidden="true"></div>
        </div>
    </section>

    <section class="sale-section sale-highlight sale-animate" id="sale-why">
        <header class="sale-section__head">
            <h3>Почему это выгоднее, чем продавать самому</h3>
        </header>
        <div class="sale-highlight__grid">
            <article class="sale-highlight__card sale-animate">
                <div class="sale-highlight__icon" aria-hidden="true">
                    <svg viewBox="0 0 64 64" focusable="false">
                        <path d="M16 28 L28 16 L36 24 L48 12 L56 20 L36 40 Z" fill="#e2001a" opacity="0.8" />
                        <path d="M16 48 L48 48" stroke="#002e66" stroke-width="4" stroke-linecap="round" />
                    </svg>
                </div>
                <p>- Не тратите время на звонки и встречи.</p>
            </article>
            <article class="sale-highlight__card sale-animate">
                <div class="sale-highlight__icon" aria-hidden="true">
                    <svg viewBox="0 0 64 64" focusable="false">
                        <rect x="10" y="12" width="44" height="40" rx="8" fill="rgba(226,0,26,0.12)" />
                        <path d="M24 24 H40 V40 H24 Z" fill="#fff" stroke="#e2001a" stroke-width="2" />
                        <path d="M20 18 H44" stroke="#002e66" stroke-width="3" stroke-linecap="round" />
                    </svg>
                </div>
                <p>- Избегаете риска с поддельными деньгами и серыми схемами.</p>
            </article>
            <article class="sale-highlight__card sale-animate">
                <div class="sale-highlight__icon" aria-hidden="true">
                    <svg viewBox="0 0 64 64" focusable="false">
                        <circle cx="20" cy="24" r="10" fill="rgba(226,0,26,0.15)" />
                        <path d="M18 38 L26 46 L46 26" stroke="#e2001a" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M12 50 H52" stroke="#002e66" stroke-width="4" stroke-linecap="round" />
                    </svg>
                </div>
                <p>- Мы умеем торговаться и получаем лучшую цену.</p>
            </article>
            <article class="sale-highlight__card sale-animate">
                <div class="sale-highlight__icon" aria-hidden="true">
                    <svg viewBox="0 0 64 64" focusable="false">
                        <path d="M12 28 L32 16 L52 28 V46 C52 52 48 56 42 56 H22 C16 56 12 52 12 46 Z" fill="rgba(0,46,102,0.08)" stroke="#002e66" stroke-width="2" />
                        <path d="M20 34 L28 42 L44 26" stroke="#e2001a" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <p>- Покупатель доверяет автосалону больше, чем частнику.</p>
            </article>
        </div>
    </section>

    <section class="sale-section sale-security sale-section--accent sale-animate" id="sale-security">
        <header class="sale-section__head">
            <h3>Безопасность сделки</h3>
        </header>
        <div class="sale-security__content">
            <div class="sale-security__icon" aria-hidden="true">
                <svg viewBox="0 0 80 80" focusable="false">
                    <path d="M40 8 L66 18 V38 C66 54 55 68 40 72 C25 68 14 54 14 38 V18 Z" fill="#ffffff" opacity="0.95" />
                    <path d="M40 8 L66 18 V38 C66 54 55 68 40 72 C25 68 14 54 14 38 V18 Z" stroke="#e2001a" stroke-width="3" fill="none" />
                    <path d="M28 42 L38 52 L54 32" stroke="#002e66" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <p>Мы работаем только официально: договор комиссии, все расчёты через банк или наличными под отчёт. Автомобиль хранится на нашей охраняемой стоянке.</p>
        </div>
    </section>

    <section class="sale-section sale-faq sale-animate" id="sale-faq">
        <header class="sale-section__head">
            <h3>FAQ</h3>
        </header>
        <div class="sale-faq__list" role="list">
            <article class="sale-faq__item sale-animate" role="listitem">
                <button class="sale-faq__question" type="button" aria-expanded="false">
                    <span class="sale-faq__icon" aria-hidden="true"></span>
                    <span class="sale-faq__text"><strong>– Сколько стоит услуга?</strong></span>
                </button>
                <div class="sale-faq__answer" hidden>
                    <p>Мы работаем по договору комиссии: процент только после продажи.</p>
                </div>
            </article>
            <article class="sale-faq__item sale-animate" role="listitem">
                <button class="sale-faq__question" type="button" aria-expanded="false">
                    <span class="sale-faq__icon" aria-hidden="true"></span>
                    <span class="sale-faq__text"><strong>– Сколько времени занимает продажа?</strong></span>
                </button>
                <div class="sale-faq__answer" hidden>
                    <p>В среднем от нескольких дней до пары недель – зависит от модели и состояния.</p>
                </div>
            </article>
            <article class="sale-faq__item sale-animate" role="listitem">
                <button class="sale-faq__question" type="button" aria-expanded="false">
                    <span class="sale-faq__icon" aria-hidden="true"></span>
                    <span class="sale-faq__text"><strong>– Что если машина не продастся?</strong></span>
                </button>
                <div class="sale-faq__answer" hidden>
                    <p>Вы в любой момент можете забрать авто обратно, без штрафов.</p>
                </div>
            </article>
        </div>
    </section>

    <section class="sale-section sale-cta sale-section--accent sale-animate" id="sale-cta">
        <div class="sale-cta__content">
            <div class="sale-cta__spark sale-floating" aria-hidden="true"></div>
            <h3>Призыв к действию</h3>
            <p>Привезите свой автомобиль сегодня – и уже завтра он появится в продаже на всех площадках. Оставьте заявку прямо сейчас!</p>
            <button class="sale-btn sale-btn--primary" type="button" data-sale-form-trigger="#sale-form">Оставить заявку</button>
        </div>
    </section>

    <section class="sale-form sale-animate" id="sale-form" hidden aria-hidden="true">
        <div class="sale-form__wrap">
            <script data-b24-form="inline/42/u65756" data-skip-moving="true">
                (function(w,d,u){
                var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/180000|0);
                var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/form/loader_42.js');
            </script>
        </div>
    </section>
</div>
