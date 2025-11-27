<script type="text/javascript">
jQuery(document).on(
    "artify_on_load artify_after_submission artify_after_ajax_action", 
    function (event, container) {

        jQuery("<?php echo $elementName; ?>").fileinput({

            showUpload: false,
            showClose: false,
            showRemove: false,
            initialPreviewAsData: true,
            overwriteInitial: false,

        <?php
            if(isset($params)) echo implode(', ', array_map(
                fn($v, $k) => $k . ':' . $v,
                $params,
                array_keys($params)
            ));
        ?>
        });

});
</script>
