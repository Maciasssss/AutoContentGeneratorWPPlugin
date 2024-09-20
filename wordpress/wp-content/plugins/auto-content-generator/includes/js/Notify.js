jQuery(document).ready(function($) {
    var $successNotice = $('#acg-success-notice');

    if ($successNotice.length) {
        setTimeout(function() {
            $successNotice.fadeOut('slow');
        }, 5000); // 5 seconds
    }
});
