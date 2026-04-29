<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$c = uuopera_contacts_get_data();
$mapKey = htmlspecialchars($c['map_api_key'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
$mapLoc = htmlspecialchars($c['map_latlng'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
$fbImg = htmlspecialchars($c['feedback_image'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
$formAction = htmlspecialchars($c['form_action'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
?>
<main class="page-padding">
    <div class="flex flex-col gap-5 md:gap-6 lg:grid lg:grid-cols-2 lg:gap-5 lg:pt-8 wrapper-main wrapper-max" data-header-color-schema="beige">
        <div class="flex flex-col justify-end gap-5 md:gap-6 pt-12 md:pt-28">
            <div class="flex flex-col gap-12 md:gap-20 2xl:gap-25">
                <h1 class="text-h1">Контакты</h1>
                <div class="grid grid-cols-2 gap-5 text-p2">
                    <div><?= $c['address_html'] ?></div>
                    <?= $c['grid_html'] ?>
                </div>
            </div>
            <div>
                <div class="relative pb-[100%] sm:pb-16/9">
                    <div class="absolute inset-0 bg-white" data-map></div>
                </div>
                <script src="https://api-maps.yandex.ru/2.1/?apikey=<?= $mapKey ?>&lang=ru_RU" type="text/javascript"></script>
                <script>
                    window.officeLocation = '<?= $mapLoc ?>';
                </script>
            </div>
        </div>
        <div class="bg-white -mx-3 md:mx-0 flex items-center">
            <div class="flex justify-center items-center py-25 wrapper-main w-full">
                <div class="w-full sm:max-w-[340px] flex flex-col gap-13">
                    <div class="flex flex-col gap-10 items-center text-center">
                        <div class="text-p2">Мы открыты к вашим <br>предложениям</div>
                        <div class="w-15">
                            <div class="relative pb-[145%]">
                                <img src="<?= $fbImg ?>" alt="Мы открыты к вашим предложениям" class="absolute image-cover">
                            </div>
                        </div>
                    </div>

                    <div class="relative">
                        <form action="<?= $formAction ?>" class="flex flex-col gap-5" data-feedback-form="feedback">
                            <input type="hidden" name="subject" value="Обратная связь">
                            <div class="flex flex-col gap-2.5">
                                <div class="group relative" data-form-field="name">
                                    <input type="text" name="name" placeholder="Имя" class="input-text" data-rules="required|regex:/^[а-яА-Я\- ]{2,}$/">
                                    <div class="form-field-error hidden group-[&.invalid]:block absolute left-0 top-full" data-error-for="name"></div>
                                    <div class="absolute top-[50%] right-0 -translate-y-[50%] text-p2">*</div>
                                </div>
                                <div class="group relative" data-form-field="phone">
                                    <input type="tel" name="phone" placeholder="Телефон" class="input-text" data-rules="required|regex:/^\+7 \(\d{3}\) \d{3}\-\d{2}\-\d{2}$/">
                                    <div class="form-field-error hidden group-[&.invalid]:block absolute left-0 top-full" data-error-for="phone"></div>
                                    <div class="absolute top-[50%] right-0 -translate-y-[50%] text-p2">*</div>
                                </div>
                                <div>
                                    <textarea name="message" placeholder="Ваше сообщение" rows="5" class="input-textarea"></textarea>
                                </div>
                            </div>
                            <div class="flex justify-between gap-3">
                                <label class="checkable checkbox">
                                    <input type="checkbox" value="Да" name="policy" class="peer/input" data-rules="required">
                                    <i class="peer-checked/input:after:opacity-100"></i>
                                    <span class="text-xs">
                                        Я даю согласие на обработку<br><a href="/soglasie-na-obrabotku-personalnykh-d/" target="_blank" class="underline">персональных данных</a>
                                    </span>
                                </label>
                                <button type="submit" class="group pl-1.5 pt-1.5 pb-2.5 flex justify-between items-center gap-5 text-p2 border-b border-current disabled:pointer-events-none disabled:opacity-60" data-form-submit-button disabled>
                                    <span>отправить</span>
                                    <span class="flex items-center">
                                        <span class="w-6 h-0.5 bg-current group-hover:scale-x-125 origin-right transition-transform duration-300"></span>
                                        <svg class="w-[8px] h-[10px] fill-current">
                                            <use xlink:href="#arrow-tip"></use>
                                        </svg>
                                    </span>
                                </button>
                            </div>
                        </form>
                        <div class="hidden [&.active]:flex flex-col justify-between items-center absolute top-0 left-0 w-full h-full bg-white" data-feedback-form-submit-result-for="feedback">
                            <div class="w-15 border-b border-current"></div>
                            <div class="text-xl/[1em] md:text-2xl tracking-wider text-center" data-feedback-form-submit-result-desc></div>
                        </div>
                        <div class="hidden [&.active]:flex absolute top-0 left-0 w-full h-full bg-white justify-center items-center" data-feedback-form-loader-for="feedback">
                            <div class="spinner w-12 h-12 border-4"></div>
                        </div>
                        <script>
                            function addSubmitMessages() {
                                var messages = {
                                    successDesc: 'Спасибо за вопрос.<br>Мы слышим вас. В скором<br>времени ответим',
                                    errorDesc: 'Что-то пошло не так, пожалуйста попробуйте еще раз'
                                }
                                if (!window.submitResultMessages) {
                                    window.submitResultMessages = {};
                                }
                                window.submitResultMessages['feedback'] = messages;
                            }
                            addSubmitMessages();
                        </script>
                    </div>

                </div>
            </div>
        </div>
    </div>
</main>
