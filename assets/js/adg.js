;(function ($) {
  $(document).ready(function ($) {
    var instanse;

    function Position(element) {
      var height = element.height();
      var offset = element.offset();
      var startViewPosition = offset.top;

      function changeData() {
        height = element.height();
        offset = element.offset();
        startViewPosition = offset.top;
      }

      $(window).resize(changeData);
      setInterval(changeData, 1000);

      return {
        getOffset: function () {
          return offset;
        },

        getHeight: function () {
          return height;
        },

        getEnterPosition: function () {
          return startViewPosition;
        }
      };
    }

    function Adg(mark, adgContent) {
      this._mark = mark;
      this._container = adgContent;
      this._allowView = true;
      this._markPosition = null;
      this._localStorage = window.localStorage;
      this._maxScreenWidth = 960;

      this.initialize();
    }

    Adg.prototype.initialize = function () {
      var windowObj = $(window);
      this.isClickedBtn(false);
      windowObj.resize(this.handlerWindowResize.bind(this));
      windowObj.scroll(this.handlerWindowScroll.bind(this));
      $('body').on('click', '.js-adg-btn', this.handlerClickControlBtn.bind(this));
      setInterval(this.handlerSizeContent.bind(this), 200);
    };

    Adg.prototype.handlerWindowScroll = function (event) {
      if (this.canShow()) {
        this.showBlock();
      }
    };

    Adg.prototype.handlerWindowResize = function (event) {
      if (this.isShowScreenSize()) {
        if (this.canShow()) {
          this.showBlock();
        }
      } else {
        this.hideBlock();
      }
    };

    Adg.prototype.handlerSizeContent = function (event) {
      if (this.canShow()) {
        this.showBlock();
      }
    };

    Adg.prototype.handlerClickControlBtn = function () {
      this.isClickedBtn(true);
      this.hideBlock();
    };

    Adg.prototype.canShow = function () {
      return this.isAllowView() && this.isShowScreenSize() && !this.isClickedBtn();
    };

    Adg.prototype.showBlock = function () {
      var htmlEl = $('html');
      var container = this.getContainer();
      var markPosition = this.getMarkPosition();
      var dgbPage = $('.js-adg-page');
      var containerHeight = container.find('.js-agb-script').outerHeight();
      var htmlMarginTop = parseInt(htmlEl.css('marginTop'));
      var htmlPaddingTop = parseInt(htmlEl.css('paddingTop'));
      var btnWrapper = container.find('.js-adg-btn-wrapper');
      var btnHeight = btnWrapper.outerHeight();
      var height = markPosition.getEnterPosition() + containerHeight - htmlMarginTop - htmlPaddingTop + btnHeight;

      dgbPage.css({
        'height': height,
        'max-height': height
      });

      dgbPage.addClass('overflow_hidden');
      htmlEl.addClass('adg-show');
    };

    Adg.prototype.hideBlock = function () {
      var dgbPage = $('.js-adg-page');
      var htmlEl = $('html');

      dgbPage.css({
        'height': 'auto',
        'max-height': 'inherit'
      });

      dgbPage.removeClass('overflow_hidden');
      htmlEl.removeClass('adg-show');
    };

    Adg.prototype.getMarkPosition = function () {
      var mark = this.getMark();
      if (!this._markPosition) {
        this._markPosition = new Position(mark);
      }
      return this._markPosition;
    };

    Adg.prototype.getMark = function () {
      return this._mark;
    };

    Adg.prototype.getContainer = function () {
      return this._container;
    };

    Adg.prototype.getScreenMaxWidth = function () {
      return this._maxScreenWidth;
    };

    Adg.prototype.getLocalStorage = function () {
      return this._localStorage;
    };

    Adg.prototype.isShowScreenSize = function () {
      var maxShowScreenSize = this.getScreenMaxWidth();
      return $(window).outerWidth() <= maxShowScreenSize;
    };

    Adg.prototype.isClickedBtn = function (newValue) {
      var localStorage = this.getLocalStorage();
      if (typeof newValue === 'boolean') {
        localStorage.setItem('adg_btn_is_clicked', newValue);
      }
      return localStorage.getItem('adg_btn_is_clicked') !== 'false';
    };

    Adg.prototype.isAllowView = function (newValue) {
      if (typeof newValue === 'boolean') {
        this._allowView = newValue;
      }
      return this._allowView;
    };

    Adg.prototype.isShowPosition = function () {
      var scrollTop = $(window).scrollTop();
      var height = $(window).height();
      var markPosition = this.getMarkPosition();

      if ((scrollTop + height) >= markPosition.getEnterPosition()) {
        return true;
      }
    };

    var adgMark = $('.js-adg-page-content .js-agb-mark');
    var adgContent = $('.js-adg-content');
    if (adgMark.length > 0) {
      instanse = new Adg(adgMark, adgContent);
    }

  });
})(jQuery);