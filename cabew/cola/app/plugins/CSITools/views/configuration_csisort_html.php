<h1>Campi di ordinamento</h1>
<form action="test">
	<div>
		<strong>Campo di ordinamento 1: </strong>
		<select name="sorting_field[1]">
			<option value="titolo">Titolo</option>
			<option value="data_cron">Data cronologia</option>
			<option value="tipo_scheda">Tipo scheda</option>
		</select>
		<select name="sorting_mode[1]">
			<option value="asc">ascendente</option>
			<option value="desc">discendente</option>
		</select>
	</div>
	<div>
		<strong>Campo di ordinamento 2: </strong>
		<select name="sorting_field[2]">
			<option value="">-</option>
			<option value="titolo">Titolo</option>
			<option value="data_cron">Data cronologia</option>
			<option value="tipo_scheda">Tipo scheda</option>
		</select>
		<select name="sorting_mode[2]">
			<option value="asc">ascendente</option>
			<option value="desc">discendente</option>
		</select>
	</div>
	<div>
		<strong>Campo di ordinamento 3: </strong>
		<select name="sorting_field[3]">
			<option value="">-</option>
			<option value="titolo">Titolo</option>
			<option value="data_cron">Data cronologia</option>
			<option value="tipo_scheda">Tipo scheda</option>
		</select>
		<select name="sorting_mode[3]">
			<option value="asc">ascendente</option>
			<option value="desc">discendente</option>
		</select>
	</div>
	<div><br /><input type="button" class="cancel" value="Annulla" /> <input type="submit" value="Ordina" /></div>
</form>