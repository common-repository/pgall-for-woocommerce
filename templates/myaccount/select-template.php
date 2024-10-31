<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script type="text/template" id="tmpl-select-item-template">
    <div class="pafw-select-item" data-slug="{{{ data.params.slug }}}">
        <# if ( data.params.thumbnail ) { #>
        <div class="pafw-select-item-thumbnail" style="background-image: url({{{ data.params.thumbnail }}}); margin: 5px;"></div>
        <# } #>

        <div class="pafw-select-item-content">
            <p class="title">{{{ data.params.title }}}</p>
            <span>{{{ data.params.description }}}</span>
        </div>
    </div>
</script>
