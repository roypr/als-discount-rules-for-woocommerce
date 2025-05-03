;
(function($){
    $(document).ready(function(){
        if(typeof alsDrw !== 'undefined' && alsDrw.hasOwnProperty('notice')){
            var html = '<div class="als-drw-notice-container"><div class="als-drw-notice">' + alsDrw.notice.text + '</div></div>';

            if(alsDrw.notice.show == 'yes'){
                $('body').prepend(html);
            }
        }
    });
})(jQuery);