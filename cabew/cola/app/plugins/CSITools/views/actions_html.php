<?php

$actions = $this->getVar('HTMLActions');

?>
<script>
	(function($){
		 $('#leftNavSidebar').html(`<?php echo $actions; ?>`);
	})(jQuery);
</script>