jQuery(function ($) {
    $('.faq-question').click(function (ev) {
        ev.preventDefault();
        $('.faq .faq-answer.active').toggleClass('active').slideToggle('fast');
        $(this).next('.faq-answer').toggleClass('active').slideToggle('fast');
    });
});