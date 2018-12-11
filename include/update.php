<div class="wrap">
	<h1>アップデート</h1>	
	<form method="get" action="<?php print $_SERVER['SCRIPT_NAME']; ?>">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">CSVのアップロード</th>
					<td>
						<input name="import" type="text" value="<?php print $this->get_data['import']; ?>" class="regular-text" readonly>
						<span class="button js-add-csv">CSVを追加</span>
					</td>
				</tr>
			</tbody>
		</table>
		<?php wp_nonce_field('nonce_csv_free_posts', '_wpnonce_csv_free_posts'); ?>
		<input type="hidden" name="page" value="csv_free_posts">
		<p class="submit">
			<button type="submit" id="update_url" class="button-primary" >更新する</button>			
			<img class="load" style="display: none;" src="<?php print $this->path; ?>/data/images/icon_loader.gif">
		</p>
	</form>
</div>