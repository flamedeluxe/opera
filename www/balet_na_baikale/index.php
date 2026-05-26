<?php
declare(strict_types=1);
// Standalone landing page — bypasses Bitrix template entirely, matching WP page-landing.php
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Балет на Байкале | Бурятия</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css"/>
    <style>
        @font-face {
            font-family: 'Playfair Display';
            src: url('/wp-content/themes/uuopera/assets/fonts/PlayfairDisplay-Regular.woff2') format('woff2'),
                 url('/wp-content/themes/uuopera/assets/fonts/PlayfairDisplay-Regular.woff') format('woff');
        }
        @font-face {
            font-family: 'Arsenal';
            src: url('/wp-content/themes/uuopera/assets/fonts/Arsenal-Regular.woff2') format('woff2'),
                 url('/wp-content/themes/uuopera/assets/fonts/Arsenal-Regular.woff') format('woff');
        }
        .bgTeal { background: rgba(245, 239, 235, 1); }
        .btn { border-radius: 0 !important; }
        .custum-btn { border-radius: 25; border-color: #574845; }
        .btn-outline-dark { border-color: #574845 !important; }
        .pfd { font-family: 'Playfair Display', Serif; }
        .h1 {
            display: flex;
            align-items: center;
            text-transform: uppercase;
            width: 100%;
            justify-content: center;
            font-weight: 700;
        }
        .h1:before {
            content: '';
            display: inline-block;
            width: 60px;
            height: 2px;
            background: #574845;
            margin-right: 1rem;
            margin-top: .5rem;
        }
        .h1:after {
            content: '';
            display: inline-block;
            width: 60px;
            height: 2px;
            background: #574845;
            margin-left: 1rem;
            margin-top: .5rem;
        }
        body {
            color: #574845;
            font-family: 'Arsenal', sans-serif;
            padding-top: 100vh;
            font-size: 18px;
        }
        header {
            border-bottom: 1px solid rgba(255,255,255,.5);
            position: absolute;
            top: 0;
            width: 100%;
        }
        .logo { width: 54px; height: 22px; fill: #fff; flex: none; }
        .logoDescr {
            font-family: 'Playfair Display', Serif;
            text-transform: uppercase;
            font-size: 0.75rem;
            line-height: 1em;
            font-weight: 400;
        }
        .buyBtnWrap { border-left: 1px solid rgba(255,255,255,.5); }
        .bgPic {
            position: absolute;
            top: 0; bottom: 0; left: 0; right: 0;
            background: url('/wp-content/uploads/2026/01/dsc_2216-scaled.jpg') no-repeat center;
            background-size: cover !important;
            height: 100vh;
        }
        .bgPic:after {
            content: '';
            display: block;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0,0,0,0.55) 0%, rgba(0,0,0,0.3) 49%, rgba(0,0,0,0.55) 100%);
        }
        .bgPic__dates {
            position: absolute;
            top: 15vh;
            left: 4rem;
            right: 4rem;
        }
        .bgPic__date { font-size: 2.5rem; font-weight: 700; }
        .line1 { line-height: 1; margin-bottom: .5rem; }
        .bgPic__place { font-size: 1.25rem; line-height: 1.2; color: #ffffffde; font-weight: 700; }
        .bgPic__text { position: absolute; bottom: 4rem; left: 4rem; right: 4rem; }
        .bgPic__name { font-size: 3rem; text-transform: uppercase; margin-bottom: 0; }
        .bgPic__descr { font-size: 1.75rem; text-transform: uppercase; }
        .bgPic__b2 {
            font-size: 1.5rem;
            border: 2px solid rgba(255,255,255,.3);
            padding: .5rem;
            flex: none;
            height: 3rem;
            width: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            align-self: end;
        }
        .bgPic__descr2 { line-height: 1; text-transform: uppercase; }
        .bgPic__arrow { position: absolute; bottom: 2rem; left: 50%; }
        .cropPic { width: 500px; margin-top: -140px; }
        .thquote:before {
            content: '\201C';
            position: absolute;
            top: 0; left: -2.5rem;
            font-size: 5rem;
            line-height: 1;
        }
        .thquote span { position: relative; }
        .thquote span:after {
            content: '\201E';
            position: absolute;
            bottom: 0; right: -2.5rem;
            font-size: 5rem;
            line-height: 1;
        }
        .thquote { font-size: 1.5rem; position: relative; }
        .footerBlock > a { display: block; text-decoration: none; color: #574845; }
        .soc { justify-content: center; }
        .soc a { text-decoration: none; color: #574845; margin: 0 .5rem; }
        .swiper-container { width: 100%; overflow: hidden; position: relative; }
        .swiper-pagination-bullet-active { background: #574845; }
        .swiper-pagination-bullet { border: 2px solid rgba(255,255,255,.8); width: 12px; height: 12px; }
        .radario-button__main { font-size: 16px !important; font-weight: 400 !important; }
        @media (max-width: 768px) {
            .bgPic__dates { left: 0; right: 0; }
            .bgPic__text { left: 1.5rem; right: 1.5rem; }
            .soc { justify-content: left; }
        }
    </style>
</head>
<body>
    <script async src="https://culturaltracking.ru/static/js/spxl.js?pixelId=12122" data-pixel-id="12122"></script>
    <script src="https://radario.ru/frontend/src/api/openapi/openapi.js"></script>

    <div class="bgPic text-white">
        <header>
            <div class="d-flex align-items-center">
                <a href="/" class="d-flex align-items-center px-3 py-3 link-light text-decoration-none">
                    <svg class="logo me-3" viewBox="0 0 54 22">
                        <path d="M0.0131526 0V22H2.57275V10.2647C2.57275 8.29071 4.29011 6.67205 6.40884 6.67205C8.52758 6.67205 10.2449 8.28084 10.2449 10.2647V22H12.8045V10.2647C12.8045 8.29071 14.5219 6.67205 16.6406 6.67205C18.7594 6.67205 20.4767 8.28084 20.4767 10.2647V22H23.0363V10.2647C23.0363 8.29071 24.7537 6.67205 26.8724 6.67205C28.9912 6.67205 30.6954 8.26769 30.7085 10.2384V22H33.2714V10.2647C33.2714 8.29071 34.9888 6.67205 37.1075 6.67205C39.2262 6.67205 40.9436 8.28084 40.9436 10.2647V22H43.5032V10.2647C43.5032 8.29071 45.2205 6.67205 47.3393 6.67205C49.458 6.67205 51.1754 8.28084 51.1754 10.2647V22H53.735V10.2647V0C53.735 0 45.4311 3.57291 26.8691 3.57291C6.70494 3.57291 0 0 0 0"></path>
                    </svg>
                    <div class="logoDescr">
                        Бурятский театр<br>оперы и балета
                    </div>
                </a>
                <div class="ms-auto me-4 d-none d-md-block">
                    <a href="https://t.me/ballet_on_baikal" target="_blank" class="link-light text-decoration-none text-uppercase"><i class="fa-brands fa-telegram me-2"></i>Присоединяйся</a>
                </div>
                <div class="text-center buyBtnWrap px-3 ms-auto ms-md-0">
                    <script>radario.Widgets.Afisha({"params":{"accentColor":"rgba(30, 21, 18, 1)","textBtnColor":"#FFFFFF","textColor":"#3D3634","backgroundColor":"rgba(245, 239, 235, 1)"},"buttonText":"КУПИТЬ БИЛЕТЫ","buttonPadding":"6px 12px","buttonBorderRadius":"0","standalone":false,"createButton":true,"key":"0748746fd619a7d06c74e5fe4d667ee08b6b08838baf8f0"});</script>
                </div>
            </div>
        </header>

        <div class="bgPic__dates row justify-content-between text-center">
            <div class="col-6 d-md-none">
                <div class="bgPic__date line1">14 и 15 июля</div>
                <div class="bgPic__place">г. Улан-Удэ, ул. Ленина 51</div>
                <div class="bgPic__date line1">17 и 18 июля</div>
                <div class="bgPic__place">с. Турка</div>
            </div>
            <div class="col-6 col-md-3 d-none d-md-block">
                <div class="bgPic__date">14, 15 июля в&nbsp;18:30</div>
                <div class="bgPic__place">Бурятский театр оперы и балета<br>г. Улан-Удэ, ул. Ленина 51</div>
                <p></p>
                <div>
                    <script>radario.Widgets.Afisha({"params":{"accentColor":"rgba(30, 21, 18, 1)","textBtnColor":"#FFFFFF","textColor":"#3D3634","backgroundColor":"rgba(245, 239, 235, 1)"},"buttonText":"КУПИТЬ БИЛЕТЫ","buttonPadding":"6px 12px","buttonBorderRadius":"0","standalone":false,"createButton":true,"key":"07487469f1de29ac0898e099a189fb18eff011d4c0e35ce"});</script>
                </div>
            </div>
            <div class="col-6 col-md-3 d-none d-md-block">
                <div class="bgPic__date">17 и 18 июля в&nbsp;16:00</div>
                <div class="bgPic__place">Участок «Пески»<br>с. Турка</div>
                <p></p>
                <div>
                    <script>radario.Widgets.Afisha({"params":{"accentColor":"rgba(30, 21, 18, 1)","textBtnColor":"#FFFFFF","textColor":"#3D3634","backgroundColor":"rgba(245, 239, 235, 1)"},"buttonText":"КУПИТЬ БИЛЕТЫ","buttonPadding":"6px 12px","buttonBorderRadius":"0","standalone":false,"createButton":true,"key":"0748746b7dcea5159067326a56f82784c9649ee8fb1c1c0"});</script>
                </div>
            </div>
        </div>

        <div class="bgPic__text row justify-content-between mb-3 mb-md-0">
            <div class="bgPic__b1 col-12 col-md-5">
                <div style="max-width: 350px; height: 100px; background: url('/wp-content/uploads/2026/05/pfki_b.svg') no-repeat; background-size: contain !important;" class="mb-4"></div>
                <h1 class="bgPic__name">Балет на&nbsp;Байкале</h1>
                <div class="bgPic__descr mb-3">Бурятия</div>
                <div class="bgPic__descr2 d-none d-md-block">
                    «Балет на Байкале. Бурятия» пройдёт уже пятый год подряд. За это время программа расширилась и превратилась из небольшого концерта в международный фестиваль.
                </div>
            </div>
            <div class="bgPic__b2 col-12 col-md d-none d-md-flex">
                6+
            </div>
        </div>

        <div class="bgPic__arrow" id="arrow">
            <i class="fa-solid fa-chevron-down fa-fade"></i>
        </div>
    </div>

    <section class="bgTeal py-4">
        <h2 class="text-center p-5 pfd h1 pb-0">Красавица Ангара</h2>
        <h4 class="text-center mb-4">14 и 15 июля</h4>
        <h4 class="text-center mb-4">Бурятский театр оперы и балета<br>г. Улан-Удэ, ул. Ленина 51</h4>
        <div class="container">
            <div class="row">
                <div class="col-6 col-md-3">
                    <div><b>Художник-постановщик</b> – Сергей Спевякин</div>
                    <div><b>Художник по свету</b> – Александр Романов</div>
                    <div><b>Художник по костюмам</b> – Елена Бабенко</div>
                </div>
                <div class="col-6 col-md-3">
                    <div><b>Хореография</b> – Михаила Заславского, Игоря Моисеева</div>
                    <div><b>Обновленная редакция</b> – Морихиро Ивата</div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-12 col-md-7 order-md-1 text-center mb-4 mb-md-0 pt-4 pt-md-0">
                    <img src="/wp-content/uploads/2026/01/angara-1.png" class="cropPic img-fluid">
                </div>
                <div class="col-12 col-md-5 order-md-0">
                    <h2 class="h3 mb-3">Балет «Красавица Ангара» можно назвать душой Бурятии, воплощенной в музыке и танце. Легенда о своенравной дочери Байкала Ангаре, сбежавшей к любимому Енисею, ожила в 1959 году.</h2>
                    <p>Либретто написал известный писатель Намжил Балдано. Музыку для национального шедевра создали два композитора, чей союз можно назвать судьбоносным. Бау Ямпилов, впитавший мелодии родной земли, и московский мастер Лев Книппер, приехавший в Бурятию и с огромным уважением изучавший её фольклор. За три месяца они написали партитуру, где тема величественного Байкала поручена медным духовым, лирическая Ангара звучит в нежных оркестровых красках, а мужественный Енисей поёт в мелодиях виолончели. Их противник, коварный Черный Вихрь, охарактеризован воинственными сигналами, напоминающими дикий клич.</p>
                    <p>14 и 15 июля 2026 года в рамках фестиваля «Балет на Байкале» эта легенда вновь оживёт на нашей сцене. Это будет особенный показ. В первый вечер партию своенравной и поэтичной Ангары исполнит ярчайшая звезда московской сцены, прима-балерина театра «Кремлёвский балет», заслуженная артистка России Екатерина Первушина.</p>
                    <p>А 15 июля зрителей ждёт сюрприз: в роли отважного Енисея выступит специальный гость.</p>
                    <p>Музыка балета развивает мелодии бурятских песен и ритмы народных танцев.</p>
                    <div class="mt-5 text-center text-md-start">
                        <script>radario.Widgets.Afisha({"params":{"accentColor":"rgba(30, 21, 18, 1)","textBtnColor":"#FFFFFF","textColor":"#3D3634","backgroundColor":"rgba(245, 239, 235, 1)"},"buttonText":"КУПИТЬ БИЛЕТЫ","buttonPadding":"6px 12px","buttonBorderRadius":"0","standalone":false,"createButton":true,"key":"07487469f1de29ac0898e099a189fb18eff011d4c0e35ce"});</script>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bgTeal py-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-12 col-md-6 offset-md-3 thquote">
                    «Все музыкальные темы персонажей балета выполняют точную художественно-смысловую роль в драматургии, они развиваются, сочетаются друг с другом в зависимости от сценических ситуаций. В балете много больших симфонических фрагментов: массовые танцы, адажио, антракт, шествие.
                    Они слушаются с живым интересом. Мелодичная, дышащая свежим весенним ароматом, национально-колорит-ная музыка балета, несомненно, займет свое место и на симфонической эстраде».
                    <span>Музыкальный критик В. Виноградов.</span>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="swiper-container swiper1">
            <div class="swiper-wrapper">
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/dsc_9387-scaled.jpg" data-fancybox="angara"><img src="/wp-content/uploads/2026/01/dsc_9387-scaled.jpg" class="img-fluid"></a></div>
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/dsc_8484-scaled.jpg" data-fancybox="angara"><img src="/wp-content/uploads/2026/01/dsc_8484-scaled.jpg" class="img-fluid"></a></div>
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/dsc_8439-scaled.jpg" data-fancybox="angara"><img src="/wp-content/uploads/2026/01/dsc_8439-scaled.jpg" class="img-fluid"></a></div>
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/5.jpg" data-fancybox="angara"><img src="/wp-content/uploads/2026/01/5.jpg" class="img-fluid"></a></div>
            </div>
            <div class="swiper-pagination pagination1"></div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 col-md-6 offset-md-3 pb-4">
                    «Красавица Ангара» превратилась из национального балета в культурный код, соединивший русскую классическую школу с бурятским эпическим сознанием. Это танец, в котором течёт сама история. Приходите услышать, как звучит Байкал, и увидеть, как танцует легенда. Начало спектаклей в 18:30.
                </div>
                <div class="col-12 col-md-6 offset-md-3">
                    <div><b>Место проведения</b>: г. Улан-Удэ, ул. Ленина 51</div>
                    <div><b>Дата</b>: 14, 15 июля 2026 года</div>
                    <div><b>Время</b>: в 18:30 – начало спектакля</div>
                    <div><b>Возрастной ценз</b>: 6+</div>
                    <div><b>Внимание</b>: проводится в здании театра оперы и балета</div>
                </div>
            </div>
        </div>
    </section>

    <div class="text-center mb-5">
        <script>radario.Widgets.Afisha({"params":{"accentColor":"rgba(30, 21, 18, 1)","textBtnColor":"#FFFFFF","textColor":"#3D3634","backgroundColor":"rgba(245, 239, 235, 1)"},"buttonText":"КУПИТЬ БИЛЕТЫ","buttonPadding":"6px 12px","buttonBorderRadius":"0","standalone":false,"createButton":true,"key":"07487469f1de29ac0898e099a189fb18eff011d4c0e35ce"});</script>
    </div>

    <section class="bgTeal py-4">
        <h2 class="text-center p-5 pfd h1 pb-0">Гала-концерт</h2>
        <h4 class="text-center mb-4">17 и 18 июля</h4>
        <h4 class="text-center mb-4">участок "Пески"<br>с. Турка</h4>
    </section>

    <section class="py-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-12 col-md-7 order-md-1 text-center mb-4 mb-md-0 pt-4 pt-md-0">
                    <img src="/wp-content/uploads/2026/01/gala-koncert-1.png" class="cropPic img-fluid">
                </div>
                <div class="col-12 col-md-5 order-md-0">
                    <h2 class="h3 mb-3">Гала-концерт «Балет на Байкале. Бурятия» — это одно из самых красивых и ожидаемых мероприятий лета на берегу Священного озера.</h2>
                    <p>Если спектакль «Красавица Ангара» — это погружение в миф, то Гала-концерт фестиваля на берегу Байкала становится его кульминацией. Это тот редкий случай, когда рамки сцены стираются самой природой, а искусство танца обретает почти сакральную силу.</p>
                    <p>В программе концерта – номера классической и современной хореографии.</p>
                    <div class="mt-5 text-center text-md-start">
                        <script>radario.Widgets.Afisha({"params":{"accentColor":"rgba(30, 21, 18, 1)","textBtnColor":"#FFFFFF","textColor":"#3D3634","backgroundColor":"rgba(245, 239, 235, 1)"},"buttonText":"КУПИТЬ БИЛЕТЫ","buttonPadding":"6px 12px","buttonBorderRadius":"0","standalone":false,"createButton":true,"key":"0748746b7dcea5159067326a56f82784c9649ee8fb1c1c0"});</script>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bgTeal py-5">
        <div class="container py-4">
            <div class="row">
                <div class="col-12 col-md-6 offset-md-3 thquote">
                    Это событие можно назвать важной вехой в истории развития бурятского балета. Яркие эмоции и незабываемые впечатления <span>обеспечены.</span>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="swiper-container swiper2">
            <div class="swiper-wrapper">
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/3.jpg" data-fancybox="baikal"><img src="/wp-content/uploads/2026/01/3.jpg" class="img-fluid"></a></div>
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/11.jpg" data-fancybox="baikal"><img src="/wp-content/uploads/2026/01/11.jpg" class="img-fluid"></a></div>
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/12.jpg" data-fancybox="baikal"><img src="/wp-content/uploads/2026/01/12.jpg" class="img-fluid"></a></div>
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/13.jpg" data-fancybox="baikal"><img src="/wp-content/uploads/2026/01/13.jpg" class="img-fluid"></a></div>
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/dsc_2219-scaled.jpg" data-fancybox="baikal"><img src="/wp-content/uploads/2026/01/dsc_2219-scaled.jpg" class="img-fluid"></a></div>
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/2.jpg" data-fancybox="baikal"><img src="/wp-content/uploads/2026/01/2.jpg" class="img-fluid"></a></div>
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/1.jpg" data-fancybox="baikal"><img src="/wp-content/uploads/2026/01/1.jpg" class="img-fluid"></a></div>
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/15.jpg" data-fancybox="baikal"><img src="/wp-content/uploads/2026/01/15.jpg" class="img-fluid"></a></div>
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/dsc_0540-1.png" data-fancybox="baikal"><img src="/wp-content/uploads/2026/01/dsc_0540-1.png" class="img-fluid"></a></div>
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/dsc_0540-2.png" data-fancybox="baikal"><img src="/wp-content/uploads/2026/01/dsc_0540-2.png" class="img-fluid"></a></div>
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/dsc_0540-3.png" data-fancybox="baikal"><img src="/wp-content/uploads/2026/01/dsc_0540-3.png" class="img-fluid"></a></div>
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/dsc_0540-4.png" data-fancybox="baikal"><img src="/wp-content/uploads/2026/01/dsc_0540-4.png" class="img-fluid"></a></div>
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/dsc_0540.png" data-fancybox="baikal"><img src="/wp-content/uploads/2026/01/dsc_0540.png" class="img-fluid"></a></div>
                <div class="swiper-slide"><a href="/wp-content/uploads/2026/01/dsc_1713-1.png" data-fancybox="baikal"><img src="/wp-content/uploads/2026/01/dsc_1713-1.png" class="img-fluid"></a></div>
            </div>
            <div class="swiper-pagination pagination2"></div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 col-md-6 offset-md-3 pb-4">
                    Представьте: летний вечер, гладь священного озера и специально возведённый помост, на котором разворачивается настоящая балетная вселенная. Два дня, 17 и 18 июля, подарят два абсолютно разных музыкальных и хореографических мира. Их объединит только феноменальное качество исполнения и берег священного Байкала.
                </div>
                <div class="col-12 col-md-6 offset-md-3">
                    <div><b>Место проведения</b>: участок «Пески», ОЭЗ «Байкальская гавань», Прибайкальский район</div>
                    <div><b>Дата</b>: 17, 18 июля 2026 года</div>
                    <div><b>Время</b>: в 16:00 – сбор гостей и развлекательная программа, в 19:00 – гала-концерт</div>
                    <div><b>Возрастной ценз</b>: 6+</div>
                </div>
            </div>
        </div>
    </section>

    <div class="text-center mb-5">
        <script>radario.Widgets.Afisha({"params":{"accentColor":"rgba(30, 21, 18, 1)","textBtnColor":"#FFFFFF","textColor":"#3D3634","backgroundColor":"rgba(245, 239, 235, 1)"},"buttonText":"КУПИТЬ БИЛЕТЫ","buttonPadding":"6px 12px","buttonBorderRadius":"0","standalone":false,"createButton":true,"key":"0748746b7dcea5159067326a56f82784c9649ee8fb1c1c0"});</script>
    </div>

    <hr class="m-5 px-5 mb-0">

    <section class="py-5 pb-3">
        <div class="container">
            <div class="row">
                <div class="col-12 col-md-6 offset-md-3">
                    Международный фестиваль «Балет на Байкале. Бурятия» пройдёт при поддержке Правительства Республики Бурятия, Министерства культуры Бурятии и Президентского фонда культурных инициатив.
                    <div class="d-flex align-items-center justify-content-around">
                        <figure class="d-flex align-items-center justify-content-center">
                            <img src="https://markhakshinov.ru/opera/amar.png" alt="Amar" />
                            <figcaption>Генеральный партнер <br><b>Проект «АМАР»</b></figcaption>
                        </figure>
                        <figure class="d-flex align-items-center justify-content-center">
                            <img src="https://markhakshinov.ru/opera/vtb.png" alt="VTB" />
                            <figcaption>Официальный партнер <br><b>Банк ВТБ</b></figcaption>
                        </figure>
                    </div>
                    <div class="d-flex align-items-center justify-content-around">
                        <p><b>Партнеры</b></p>
                    </div>
                    <div class="d-flex align-items-center justify-content-around">
                        <figure>
                            <img src="/wp-content/uploads/2026/02/ingosstrah-130h130.png" alt="Ингосстрах" />
                            <figcaption>Страховая компания <br><b>«Ингосстрах»</b></figcaption>
                        </figure>
                        <figure>
                            <img src="/wp-content/uploads/2026/02/viktorija-klinik-130h130.png" alt="Виктория Клиник" />
                            <figcaption>Стоматологическая <br>клиника <br><b>«Виктория Клиник»</b></figcaption>
                        </figure>
                        <figure>
                            <img src="/wp-content/uploads/2026/02/gorjachinsk-130h130.png" alt="Горячинск" />
                            <figcaption>Курорт <b>«Горячинск»</b></figcaption>
                        </figure>
                    </div>
                    <div class="d-flex align-items-center justify-content-around">
                        <p><b>Информационные партнеры</b></p>
                    </div>
                    <div class="d-flex align-items-center justify-content-around">
                        <figure>
                            <img src="/wp-content/uploads/2026/02/vk-130h130.png" alt="Vk.com" />
                            <figcaption class="d-flex justify-content-center"><b>«Vk.com»</b></figcaption>
                        </figure>
                        <figure>
                            <img src="/wp-content/uploads/2026/02/ok-130h130.png" alt="Ok.ru" />
                            <figcaption class="d-flex justify-content-center"><b>«Ok.ru»</b></figcaption>
                        </figure>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <hr class="mx-5 px-5 mt-0">

    <footer class="container py-5">
        <div class="row">
            <div class="col-6 col-md-3 footerBlock footerBlock_1 mb-4 mb-md-0">
                <a href="/missiya-i-cennosti/">О театре</a>
                <a href="/projects/">Проекты</a>
                <a href="/contacts/">Контакты</a>
            </div>
            <div class="col-6 col-md-3 footerBlock footerBlock_1 mb-4 mb-md-0">
                <a href="/afisha/">Афиша</a>
                <a href="https://radario.ru/customer/afisha/0748746fd619a7d06c74e5fe4d667ee08b6b08838baf8f0?openAsLinkKey=fx411z69zh">Купить билеты</a>
                <a href="/category/news/">Новости</a>
            </div>
            <div class="col-6 col-md-3 footerBlock">
                <div>г. Улан-Удэ, Ленина 51</div>
                <a href="tel:+73012213600">8 (3012) 21-36-00</a>
                <a href="mailto:uuopera@govrb.ru">uuopera@govrb.ru</a>
            </div>
            <div class="col-6 col-md-3">
                <div class="d-flex soc">
                    <a target="_blank" href="https://vk.com/uuopera"><i class="fa-brands fa-vk fa-fw fa-lg"></i></a>
                    <a target="_blank" href="https://ok.ru/group/70000001560984"><i class="fa-brands fa-odnoklassniki fa-fw fa-lg"></i></a>
                    <a target="_blank" href="https://t.me/uuopera03"><i class="fa-brands fa-telegram fa-fw fa-lg"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://radario.ru/frontend/src/api/openapi/openapi.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/976a967e92.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script>
        const swiper = new Swiper('.swiper1', {
            slidesPerView: 3,
            loop: true,
            autoplay: { delay: 2500, disableOnInteraction: true },
            pagination: { el: '.pagination1' },
            breakpoints: { 0: { slidesPerView: 1 }, 768: { slidesPerView: 3 } },
        });
        const swiper2 = new Swiper('.swiper2', {
            slidesPerView: 3,
            loop: true,
            autoplay: { delay: 2500, disableOnInteraction: true },
            pagination: { el: '.pagination2' },
            breakpoints: { 0: { slidesPerView: 1 }, 768: { slidesPerView: 3 } },
        });
        window.onscroll = function() {
            document.getElementById("arrow").style.display = window.scrollY > 250 ? 'none' : 'block';
        };
        Fancybox.bind("[data-fancybox]", {});
    </script>
</body>
</html>
