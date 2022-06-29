<?php
	$displays = $this->getVar('displays');
?>
<h1>Stampa</h1>
<form>
	<div>
		<strong>Vista: </strong>
		<select name="display_code">
			<?php foreach($displays as $id => $code) { ?>
				<option value="<?php print $id; ?>"><?php print $code; ?></option>
			<?php } ?>
		</select>
	</div>
	<div><br /><input type="button" class="cancel" value="Annulla" /> <input type="submit" value="Stampa" /></div>
</form>