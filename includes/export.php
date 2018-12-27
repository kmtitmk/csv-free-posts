<?php
/*
 * エクスポートで設定するページ
------------------------------*/
?>

<style>
.export-textarea {
	width: 100%;
	height: 300px;
}
</style>

<div class="wrap">
	<h1>エクスポート</h1>
	<form method="get" action="<?php print $_SERVER['SCRIPT_NAME']; ?>">
		<table class="form-table">
			<?php print $post_typ_tag; ?>
		</table>
		<?php wp_nonce_field('nonce_csv_free_posts_export', '_wpnonce_csv_free_posts_export'); ?>
		<input type="hidden" name="page" value="csv_free_posts_sub_export">
		<p class="submit">
			<button type="submit" id="update_url" class="button-primary" >更新する</button>
			<img class="load" style="display: none;" src="<?php print $this->path; ?>/data/images/icon_loader.gif">
		</p>
	</form>
	<?php
        if ($this->get_data['_wpnonce_csv_free_posts_export']) {
			print '<table>' . $this->export_html . '</table>';
			
			$this->export_csv_file();
			print '<a id="csv_export" class="button" href="' . $this->path . '/data/csv/export.csv">CSVのエクスポート</a>';
        }
    ?>
</div>