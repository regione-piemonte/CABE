<?php
	$singleLevel = $this->getVar('singleLevel');
?><h1>Segnatura definitiva</h1>
<form>
	<div>
		<strong>Modalità di segnatura: </strong>
		<select name="mode">
			<?php if($singleLevel){ ?>
				<option value="single">Corda aperta su singolo livello</option>
			<?php }else{ ?>
			<option value="close">Corda chiusa</option>
			<option value="open">Corda aperta a cascata</option>
			<?php } ?>
		</select>
	</div>
	<div><br /><strong>Prefisso (facoltativo)</strong> <input type="text" name="prefix"></div>
	<div><br /><input type="button" class="cancel" value="Annulla" /> <input type="submit" value="Assegna" /></div>
</form>