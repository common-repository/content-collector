<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WSCR_HelpPage extends Wbcr_FactoryPages402_AdminPage
{

    /**
     * The id of the page in the admin menu.
     *
     * Mainly used to navigate between pages.
     * @see FactoryPages402_AdminPage
     *
     * @since 1.0.0
     * @var string
     */
    public $id = "help";

    public $internal = false;

    /**
     * @param Wbcr_Factory401_Plugin $plugin
     */
    public function __construct(Wbcr_Factory401_Plugin $plugin)
    {
        $this->menu_post_type = WSCR_SCRAPES_POST_TYPE;
        $this->menu_title = __('Справка', 'wbcr-scrapes');
        $this->menu_icon = "\f226";
        $this->capabilitiy = "manage_options";

        parent::__construct($plugin);

        $this->plugin = $plugin;
    }

    public function assets($scripts, $styles)
    {
        $this->styles->add(WSCR_PLUGIN_URL . '/assets/css/help.css');
        $this->scripts->request('jquery');
        $this->scripts->add(WSCR_PLUGIN_URL . '/assets/js/help.js');
    }

    public function indexAction()
    {

        ?>
        <h1><?=__("Справка", "wbcr-scrapes");?></h1>
        <p><b><?=__("Содержание", "wbcr-scrapes");?></b></p>
        <ol>
            <?if(get_locale() == 'ru_RU'){?>
            <li><a href="#videos"><?=__("Видео", "wbcr-scrapes");?></a></li>
            <?}?>
            <li><a href="#faq"><?=__("FAQ", "wbcr-scrapes");?></a></li>
            <li><a href="#contacts"><?=__("Контакты", "wbcr-scrapes");?></a></li>
        </ol>

        <?if(get_locale() == 'ru_RU'){?>
        <h2 id="videos"><?=__("Видео", "wbcr-scrapes");?></h2>
        <p><?=__("Наши обучающие видео продемонстрируют Вам как настроить и начать использовать плагин", "wbcr-scrapes");?></p>
        <div class="faq-videos">
            <iframe class="faq-video" width="560" height="315" src="https://www.youtube.com/embed/W9FhuPARIDk?rel=0"
                    frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>

            <iframe class="faq-video" width="560" height="315" src="https://www.youtube.com/embed/-fKBi8VV6Wc?rel=0"
                    frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>

            <iframe class="faq-video" width="560" height="315" src="https://www.youtube.com/embed/XPf-IT2DXn4?rel=0"
                    frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>

            <iframe class="faq-video" width="560" height="315" src="https://www.youtube.com/embed/EjlK7Zm0_TY?rel=0"
                    frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
        </div>
        <?}?>

        <h2 id="faq"><?=__("FAQ", "wbcr-scrapes");?></h2>
        <ul class="faq">
            <li><a href="#" class="faq-question"><?=__("Возможно ли парсить статьи с сайта, у которого отсутствует
                    пагинация?", "wbcr-scrapes");?></a>
                <div class="faq-answer"><?=__("Да, но в таком случае вам необходимо дать парсеру ссылки на сами статьи.", "wbcr-scrapes");?>
                </div>
            </li>
            <li><a href="#" class="faq-question"><?=__("Можно спарсить любую статью?", "wbcr-scrapes");?></a>
                <div class="faq-answer"><?=__("Да.", "wbcr-scrapes");?>
                </div>
            </li>
            <li><a href="#" class="faq-question"><?=__("Я могу парсить статьи только с сайтов на Вордпрессе?", "wbcr-scrapes");?></a>
                <div class="faq-answer"><?=__("Нет, парсер работает с сайтами на любой платформе.", "wbcr-scrapes");?>
                </div>
            </li>
            <li><a href="#" class="faq-question"><?=__("У меня нет сайта на Вордпрессе, могу ли я пользоваться услугами
                    сервиса?", "wbcr-scrapes");?></a>
                <div class="faq-answer"><?=__("В данный момент  нет но мы занимаемся поиском решения этого вопроса.", "wbcr-scrapes");?>
                </div>
            </li>
            <li><a href="#" class="faq-question"><?=__("Сколько сайтов-источников я могу использовать для парсинга?", "wbcr-scrapes");?></a>
                <div class="faq-answer"><?=__("В зависимости от выбранного тарифа. По умолчанию, на бесплатном тарифе можно использовать 3 сайта источника.", "wbcr-scrapes");?>
                </div>
            </li>
            <li><a href="#" class="faq-question"><?=__("Я хочу купить новый тариф, но у меня не израсходован месячный лимит
                    парсинга на старом. Оплаченный ранее период будет аннулирован?", "wbcr-scrapes");?></a>
                <div class="faq-answer"><?=__("Нет, не израсходованный период будет компенсирован в виде денежных средств на балансе, которые можно потратить на оплату нового тарифного плана.", "wbcr-scrapes");?>
                </div>
            </li>
            <li><a href="#" class="faq-question"><?=__("Оказывает ли сервис поддержку на бесплатном тарифе?", "wbcr-scrapes");?></a>
                <div class="faq-answer"><?=__("Да.", "wbcr-scrapes");?>
                </div>
            </li>
            <li><a href="#" class="faq-question"><?=__("Не могу настроить задание. Что делать?", "wbcr-scrapes");?></a>
                <div class="faq-answer"><?=__("Обратитесь в техническую поддержку.", "wbcr-scrapes");?>
                </div>
            </li>
        </ul>


        <h2 id="contacts"><?=__("Контакты", "wbcr-scrapes");?></h2>
        <p><?=__("Не нашли ответа на свой вопрос? Или у вас есть для нас интересное предложение?", "wbcr-scrapes");?></p>
        <p><?=sprintf(__("Вы можете связаться с нами по электронной почте %s wpparser@gmail.com %s", "wbcr-scrapes"), '<a href="mailto:wpparser@gmail.com">', '</a>');?></p>
        <?php
    }

}